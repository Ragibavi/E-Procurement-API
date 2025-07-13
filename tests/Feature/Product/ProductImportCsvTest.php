<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;


class ProductImportCsvTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_import_csv_dispatches_job_successfully()
    {
        Storage::fake('local');
        $this->actingAsUser();

        $csvContent = <<<CSV
                    vendor_id,name,description,price,stock
                    11111111-1111-1111-1111-111111111111,Product A,Good,100,10
                    CSV;

        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $mockQueue = \Mockery::mock(\Illuminate\Contracts\Queue\Queue::class);
        $mockQueue->shouldReceive('push')
            ->once()
            ->with(\Mockery::on(function ($job) {
                return $job instanceof \App\Jobs\ImportProductsJob &&
                    str_starts_with($job->filePath, 'imports/') &&
                    str_ends_with($job->filePath, '.csv');
            }))
            ->andReturnTrue();

        $this->app->instance(\Illuminate\Contracts\Queue\Queue::class, $mockQueue);

        $response = $this->postJson('/api/products/import-csv', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Import job dispatched.']);
    }

    public function test_import_creates_import_directory_if_missing()
    {
        Storage::fake('local');
        $this->actingAsUser();

        $importDir = storage_path('app/imports');
        if (file_exists($importDir)) {
            $files = new \FilesystemIterator($importDir);
            foreach ($files as $file) {
                unlink($file->getRealPath());
            }
            rmdir($importDir);
        }


        $this->assertDirectoryDoesNotExist($importDir);

        $csvContent = <<<CSV
vendor_id,name,description,price,stock
11111111-1111-1111-1111-111111111111,Product A,Good,100,10
CSV;

        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $this->postJson('/api/products/import-csv', [
            'file' => $file,
        ])->assertStatus(200)
            ->assertJsonFragment(['message' => 'Import job dispatched.']);

        $this->assertDirectoryExists($importDir);
    }

    public function test_import_csv_fails_if_file_missing()
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/products/import-csv');

        $response->assertStatus(422)->assertJsonValidationErrors(['file']);
    }

    public function test_import_csv_rejects_invalid_file_type()
    {
        $this->actingAsUser();

        $file = UploadedFile::fake()->create('invalid.pdf', 10, 'application/pdf');

        $response = $this->postJson('/api/products/import-csv', [
            'file' => $file,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['file']);
    }

    public function test_import_csv_fails_gracefully_on_exception()
    {
        $this->actingAsUser();

        $file = UploadedFile::fake()->create('error.csv', 10, 'text/csv');

        $mockQueue = \Mockery::mock(\Illuminate\Contracts\Queue\Queue::class);
        $mockQueue->shouldReceive('push')
            ->once()
            ->andThrow(new \Exception('Simulated dispatch error'));

        $this->app->instance(\Illuminate\Contracts\Queue\Queue::class, $mockQueue);

        $response = $this->postJson('/api/products/import-csv', [
            'file' => $file,
        ]);

        $response->assertStatus(500)
            ->assertJsonFragment(['error' => 'Failed to dispatch job'])
            ->assertJsonFragment(['details' => 'Simulated dispatch error']);
    }

    protected function tearDown(): void
    {
        $fakePath = storage_path('fake.csv');
        if (file_exists($fakePath)) {
            unlink($fakePath);
        }
        Mockery::close();
        parent::tearDown();
    }
}

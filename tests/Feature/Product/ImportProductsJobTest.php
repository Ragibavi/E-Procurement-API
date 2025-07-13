<?php

namespace Tests\Unit;

use App\Jobs\ImportProductsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Vendor;

class ImportProductsJobTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->tempPath = storage_path('app/imports/test.csv');

        if (!file_exists(dirname($this->tempPath))) {
            mkdir(dirname($this->tempPath), 0775, true);
        }
    }

    public function test_job_processes_valid_csv_and_inserts_products()
    {
        $vendor = Vendor::factory()->create();

        $csv = "vendor_id,name,description,price,stock\n"
            . "{$vendor->id},Product A,Good Product,150.5,20\n"
            . "{$vendor->id},Product B,Another Good Product,200,10\n";

        file_put_contents($this->tempPath, $csv);

        $job = new ImportProductsJob('imports/test.csv');
        $job->handle();

        $this->assertDatabaseCount('products', 2);
        Storage::disk('local')->assertMissing('imports/test.csv');
    }

    public function test_job_skips_invalid_rows_and_processes_rest()
    {
        $vendor = Vendor::factory()->create();

        $csv = "vendor_id,name,description,price,stock\n"
            . "{$vendor->id},Valid Product,Desc,99.9,5\n"
            . "{$vendor->id},,Missing Name,100,2\n"
            . "{$vendor->id},Another Valid,Desc,150.0,1\n";

        file_put_contents($this->tempPath, $csv);

        $job = new ImportProductsJob('imports/test.csv');
        $job->handle();

        $this->assertDatabaseCount('products', 2);
    }

    public function test_job_logs_error_when_file_not_found()
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'File not found'));

        $job = new ImportProductsJob('imports/missing.csv');
        $job->handle();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_job_logs_error_when_file_cannot_be_opened()
    {
        \Illuminate\Support\Facades\Log::spy();

        file_put_contents($this->tempPath, "vendor_id,name,description,price,stock\n");
        chmod($this->tempPath, 0000);

        $job = new \App\Jobs\ImportProductsJob('imports/test.csv');
        $job->handle();

        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'Cannot open file'));

        chmod($this->tempPath, 0644);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempPath)) {
            chmod($this->tempPath, 0644);
            unlink($this->tempPath);
        }
        parent::tearDown();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;
use Tests\TestCase;
use Mockery;

class VendorUserTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(string $role = 'user'): User
    {
        $user = User::factory()->create(['role' => $role]);
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_guest_cannot_access_all_vendors()
    {
        $response = $this->getJson('/api/vendors/all');
        $response->assertStatus(401);
    }

    public function test_non_admin_gets_403_on_all_vendors()
    {
        $this->actingAsUser('user');
        $response = $this->getJson('/api/vendors/all');
        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized. Admin only.']);
    }

    public function test_admin_can_get_all_vendors()
    {
        $admin = $this->actingAsUser('admin');
        Vendor::factory()->count(2)->create();
        $response = $this->getJson('/api/vendors/all');
        $response->assertStatus(200)->assertJsonCount(2);
    }

    public function test_all_vendors_throwable_handled_and_returns_500()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $mockVendor = \Mockery::mock(Vendor::class);
        $mockVendor->shouldReceive('all')
            ->once()
            ->andThrow(new \Exception('Simulated DB error'));

        $this->app->instance(Vendor::class, $mockVendor);

        $response = $this->getJson('/api/vendors/all');

        $response->assertStatus(500)
            ->assertJsonFragment(['error' => 'Simulated DB error']);
    }

    public function test_index_success()
    {
        $user = $this->actingAsUser();
        Vendor::factory()->count(2)->create(['user_id' => $user->id]);
        $response = $this->getJson('/api/vendors');
        $response->assertStatus(200)->assertJsonCount(2);
    }

    public function test_index_failure_handled()
    {
        $this->actingAsUser();

        $mock = \Mockery::mock(Vendor::class);
        $mock->shouldReceive('where')->andThrow(new \Exception('DB error'));
        $this->app->instance(Vendor::class, $mock);

        $response = $this->getJson('/api/vendors');
        $response->assertStatus(500)->assertJsonFragment(['error' => 'DB error']);
    }

    public function test_store_success()
    {
        $this->actingAsUser();
        $payload = Vendor::factory()->make()->toArray();
        unset($payload['user_id'], $payload['id']);
        $response = $this->postJson('/api/vendors', $payload);
        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Vendor registered successfully']);
    }

    public function test_store_validation_fails()
    {
        $this->actingAsUser();
        $response = $this->postJson('/api/vendors', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'contact_person', 'phone', 'email']);
    }

    public function test_store_throwable_handled()
    {
        $this->actingAsUser();

        $mockVendor = \Mockery::mock(\App\Models\Vendor::class);
        $mockVendor->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Simulated create error'));

        $this->app->instance(\App\Models\Vendor::class, $mockVendor);

        $payload = [
            'company_name' => 'Test Company',
            'contact_person' => 'John Doe',
            'phone' => '1234567890',
            'email' => 'test@example.com',
            'address' => '123 Street',
        ];

        $response = $this->postJson('/api/vendors', $payload);

        $response->assertStatus(500)
            ->assertJsonFragment(['error' => 'Simulated create error']);
    }


    public function test_show_success()
    {
        $user = $this->actingAsUser();
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);
        $response = $this->getJson("/api/vendors/{$vendor->id}");
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $vendor->id]);
    }

    public function test_show_not_found()
    {
        $this->actingAsUser();
        $response = $this->getJson("/api/vendors/" . Str::uuid());
        $response->assertStatus(500)->assertJsonStructure(['error']);
    }

    public function test_update_success()
    {
        $user = $this->actingAsUser();
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);
        $response = $this->putJson("/api/vendors/{$vendor->id}", ['company_name' => 'Updated Name']);
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Vendor updated successfully']);
    }

    public function test_update_validation_fails()
    {
        $user = $this->actingAsUser();
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);
        $response = $this->putJson("/api/vendors/{$vendor->id}", ['email' => 'not-an-email']);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_update_vendor_throwable_handled()
    {
        $this->actingAsUser();

        $id = (string) \Illuminate\Support\Str::uuid();

        $vendorInstanceMock = \Mockery::mock();
        $vendorInstanceMock->shouldReceive('update')
            ->once()
            ->andThrow(new \Exception('Simulated update error'));

        $vendorMock = \Mockery::mock(\App\Models\Vendor::class);
        $vendorMock->shouldReceive('findOrFail')
            ->with($id)
            ->andReturn($vendorInstanceMock);

        $this->app->instance(\App\Models\Vendor::class, $vendorMock);

        $response = $this->putJson("/api/vendors/{$id}", [
            'company_name' => 'New Name'
        ]);

        $response->assertStatus(500)
            ->assertJsonFragment(['error' => 'Simulated update error']);
    }


    public function test_destroy_vendor_success()
    {
        $user = $this->actingAsUser();
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);
        $response = $this->deleteJson("/api/vendors/{$vendor->id}");
        $response->assertStatus(200)
            ->assertJson(['message' => 'Vendor deleted successfully']);
    }

    public function test_destroy_vendor_not_found()
    {
        $this->actingAsUser();
        $response = $this->deleteJson('/api/vendors/' . Str::uuid());
        $response->assertStatus(500)->assertJsonStructure(['error']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

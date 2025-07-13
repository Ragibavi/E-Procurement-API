<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_index_returns_products()
    {
        $this->actingAsUser();

        $vendor = Vendor::factory()->create();
        Product::factory()->count(2)->create(['vendor_id' => $vendor->id]);

        $response = $this->getJson('/api/products');
        $response->assertStatus(200)->assertJsonCount(2);
    }

    public function test_index_products_throwable_handled()
    {
        $this->actingAsUser();

        $mockProduct = \Mockery::mock(\App\Models\Product::class);
        $mockProduct->shouldReceive('with')
            ->with('vendor')
            ->andThrow(new \Exception('Simulated DB error'));

        $this->app->instance(\App\Models\Product::class, $mockProduct);

        $response = $this->getJson('/api/products');

        $response->assertStatus(500)
            ->assertJsonFragment(['error' => 'Failed to fetch products'])
            ->assertJsonFragment(['details' => 'Simulated DB error']);
    }

    public function test_store_product_successfully()
    {
        $this->actingAsUser();

        $vendor = Vendor::factory()->create();

        $response = $this->postJson('/api/products', [
            'vendor_id' => $vendor->id,
            'name' => 'Test Product',
            'description' => 'Great product',
            'price' => 100,
            'stock' => 10
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Product created']);
    }

    public function test_store_product_validation_fails()
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/products', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vendor_id', 'name', 'price', 'stock']);
    }

    public function test_store_product_throwable_handled()
    {
        $this->actingAsUser();

        $vendor = Vendor::factory()->create();

        $mockProduct = \Mockery::mock(\App\Models\Product::class);
        $mockProduct->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Simulated DB error'));

        $this->app->instance(\App\Models\Product::class, $mockProduct);

        $payload = [
            'vendor_id' => $vendor->id,
            'name' => 'Test Product',
            'description' => 'Great product',
            'price' => 100,
            'stock' => 10
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(500)
            ->assertJsonFragment(['error' => 'Failed to store product'])
            ->assertJsonFragment(['details' => 'Simulated DB error']);
    }

    public function test_show_product_success()
    {
        $this->actingAsUser();

        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->getJson("/api/products/{$product->id}");
        $response->assertStatus(200)->assertJsonFragment(['id' => $product->id]);
    }

    public function test_show_product_validation_fails()
    {
        $this->actingAsUser();

        $response = $this->getJson("/api/products/" . Str::uuid());
        $response->assertStatus(500)->assertJsonStructure(['error']);
    }

    public function test_update_product_success()
    {
        $this->actingAsUser();

        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Product'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Product updated']);
    }

    public function test_update_product_validation_fails()
    {
        $this->actingAsUser();

        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => '',
            'price' => null,
            'stock' => null
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'stock']);
    }

    public function test_update_product_throwable_handled()
    {
        $this->actingAsUser();

        $id = (string) \Illuminate\Support\Str::uuid();
        $productInstanceMock = \Mockery::mock();
        $productInstanceMock->shouldReceive('update')
            ->once()
            ->andThrow(new \Exception('Simulated update error'));

        $productMock = \Mockery::mock(\App\Models\Product::class);
        $productMock->shouldReceive('findOrFail')
            ->with($id)
            ->andReturn($productInstanceMock);

        $this->app->instance(\App\Models\Product::class, $productMock);

        $response = $this->putJson("/api/products/{$id}", [
            'name' => 'New Name'
        ]);

        $response->assertStatus(500)
            ->assertJsonFragment([
                'error' => 'Failed to update product',
                'details' => 'Simulated update error'
            ]);
    }

    public function test_delete_product_success()
    {
        $this->actingAsUser();

        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->deleteJson("/api/products/{$product->id}");
        $response->assertStatus(200)->assertJson(['message' => 'Product deleted']);
    }

    public function test_destroy_product_now_found()
    {
        $this->actingAsUser();
        $response = $this->deleteJson("/api/products/" . Str::uuid());
        $response->assertStatus(500)->assertJsonStructure(['error']);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}

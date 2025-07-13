<?php

namespace Tests\Feature\Auth;

use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_registration()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)->assertJson(['message' => 'User registered successfully']);
        $this->assertDatabaseHas('users', ['email' => 'user@example.com']);
    }

    public function test_registration_validation_failure()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'not-an-email',
            'password' => '123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_general_exception_handling()
    {
        $mock = Mockery::mock(UserService::class);
        $mock->shouldReceive('createUser')
            ->once()
            ->andThrow(new \Exception('Database insert failed'));

        $this->app->instance(UserService::class, $mock);

        $response = $this->postJson('/api/register', [
            'name' => 'Fail User',
            'email' => 'fail@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(500)
            ->assertJsonFragment([
                'error' => 'Registration failed',
                'details' => 'Database insert failed',
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

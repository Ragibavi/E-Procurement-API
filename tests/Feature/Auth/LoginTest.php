<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login()
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_login_with_wrong_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_invalid_email_format()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_missing_fields()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_general_exception_handling()
    {
        $mockService = \Mockery::mock(\App\Services\UserService::class);
        $mockService->shouldReceive('getUserByEmail')
            ->with('fail@example.com')
            ->andThrow(new \Exception('Something went wrong during login'));

        $this->app->instance(\App\Services\UserService::class, $mockService);

        $response = $this->postJson('/api/login', [
            'email' => 'fail@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(500)
            ->assertJsonFragment([
                'error' => 'Login failed',
                'details' => 'Something went wrong during login',
            ]);
    }


    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}

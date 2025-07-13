<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_logout()
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);
    }

    public function test_logout_general_exception_handling()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mockUser = Mockery::mock($user)->makePartial();
        $mockTokens = Mockery::mock();

        $mockUser->shouldReceive('tokens')->andReturn($mockTokens);
        $mockTokens->shouldReceive('delete')->andThrow(new \Exception('Something went wrong during logout'));

        $this->app->bind('Illuminate\Http\Request', function () use ($mockUser) {
            $request = \Illuminate\Http\Request::create('/api/logout', 'POST');
            $request->setUserResolver(fn() => $mockUser);
            return $request;
        });

        $response = $this->postJson('/api/logout');

        $response->assertStatus(500)
            ->assertJsonFragment([
                'error' => 'Logout failed',
                'details' => 'Something went wrong during logout',
            ]);
    }


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

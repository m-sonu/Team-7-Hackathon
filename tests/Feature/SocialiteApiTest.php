<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class SocialiteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_redirect_url_for_provider(): void
    {
        $provider = 'github';

        $mockProvider = \Mockery::mock('Laravel\Socialite\Two\GithubProvider');
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('redirect')->andReturnSelf();
        $mockProvider->shouldReceive('getTargetUrl')->andReturn('https://github.com/login/oauth/authorize?client_id=123');

        Socialite::shouldReceive('driver')
            ->with($provider)
            ->andReturn($mockProvider);

        $response = $this->getJson("/api/auth/{$provider}/redirect");

        $response->assertStatus(200)
            ->assertJson([
                'url' => 'https://github.com/login/oauth/authorize?client_id=123',
            ]);
    }

    public function test_it_handles_callback_and_creates_new_user(): void
    {
        $provider = 'google';

        $mockUser = \Mockery::mock('Laravel\Socialite\Two\User');
        $mockUser->shouldReceive('getId')->andReturn('123456789');
        $mockUser->shouldReceive('getName')->andReturn('Test User');
        $mockUser->shouldReceive('getNickname')->andReturn(null);
        $mockUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $mockUser->token = 'fake_token';
        $mockUser->refreshToken = 'fake_refresh_token';

        $mockProvider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('user')->andReturn($mockUser);

        Socialite::shouldReceive('driver')
            ->with($provider)
            ->andReturn($mockProvider);

        $response = $this->getJson("/api/auth/{$provider}/callback");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'provider_name',
                    'provider_id',
                ],
                'token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'provider_name' => $provider,
            'provider_id' => '123456789',
            'provider_token' => 'fake_token',
        ]);
    }

    public function test_it_handles_callback_with_access_token(): void
    {
        $provider = 'github';
        $accessToken = 'some-access-token-from-frontend';

        $mockUser = \Mockery::mock('Laravel\Socialite\Two\User');
        $mockUser->shouldReceive('getId')->andReturn('987654321');
        $mockUser->shouldReceive('getName')->andReturn('Existing User');
        $mockUser->shouldReceive('getNickname')->andReturn('existing');
        $mockUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $mockUser->token = 'new_fake_token';
        $mockUser->refreshToken = null;

        $mockProvider = \Mockery::mock('Laravel\Socialite\Two\GithubProvider');
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('userFromToken')->with($accessToken)->andReturn($mockUser);

        Socialite::shouldReceive('driver')
            ->with($provider)
            ->andReturn($mockProvider);

        // Pre-create user to test update path
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson("/api/auth/{$provider}/callback", [
            'access_token' => $accessToken
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'existing@example.com',
            'provider_name' => $provider,
            'provider_id' => '987654321',
            'provider_token' => 'new_fake_token',
        ]);
    }
}

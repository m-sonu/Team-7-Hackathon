<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Get the redirect URL for the given provider.
     */
    public function redirect(string $provider)
    {
        // For API, we return the redirect URL as JSON
        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'url' => $url,
        ]);
    }

    /**
     * Handle the callback from the provider.
     * Depending on the frontend architecture, this might receive an access token directly
     * or handle the OAuth callback itself.
     */
    public function callback(Request $request, string $provider)
    {
        try {
            // If the frontend is passing the provider's access token directly:
            if ($request->has('access_token')) {
                $socialUser = Socialite::driver($provider)->stateless()->userFromToken($request->access_token);
            } else {
                // If this endpoint is handling the OAuth callback directly
                $socialUser = Socialite::driver($provider)->stateless()->user();
            }

            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Update existing user with provider details
                $user->update([
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_token' => $socialUser->token,
                    'provider_refresh_token' => $socialUser->refreshToken,
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                    'email' => $socialUser->getEmail(),
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_token' => $socialUser->token,
                    'provider_refresh_token' => $socialUser->refreshToken,
                ]);
            }

            // Generate Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Return JSON response with the token
            return response()->json([
                'message' => 'Successfully authenticated',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Authentication failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}

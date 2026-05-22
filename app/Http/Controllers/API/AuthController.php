<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UserRegistrationRequest;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(protected UserService $userService) {}

    /**
     * Handle user registration.
     */
    public function register(UserRegistrationRequest $request): JsonResponse
    {
        try {
            $data = $this->userService->registerUser($request->validated());

            return $this->sendResponse($data, 'User registered successfully', 201);
        } catch (Exception $e) {
            return $this->sendError('Failed to register user', 500);
        }
    }

    /**
     * Handle user login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $this->userService->loginUser($request->validated());

            if (! $data) {
                return $this->sendError('Invalid credentials', 401);
            }

            return $this->sendResponse($data, 'Login successful');
        } catch (Exception $e) {
            return $this->sendError('An error occurred during login', 500);
        }
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->userService->logoutUser($request->user());

            return $this->sendResponse([], 'Logged out successfully');
        } catch (Exception $e) {
            return $this->sendError('Failed to log out', 500);
        }
    }
}

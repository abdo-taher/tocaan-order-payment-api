<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterUserDTO::fromArray($request->validated());

        $tokenDTO = $this->authService->register($dto);

        return $this->created($tokenDTO->toArray(), 'User registered successfully.');
    }

    /**
     * Login and get a JWT token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromArray($request->validated());

        $tokenDTO = $this->authService->login($dto);

        if (!$tokenDTO) {
            return $this->unauthorized('Invalid credentials.');
        }

        return $this->success($tokenDTO->toArray(), 'Login successful.');
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->success(message: 'Successfully logged out.');
    }

    /**
     * Get the authenticated user profile.
     */
    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return $this->success(new UserResource($user), 'User profile retrieved.');
    }
}

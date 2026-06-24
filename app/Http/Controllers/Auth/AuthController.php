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
use Symfony\Component\HttpFoundation\Response;

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

        return response()->json([
            'message' => 'User registered successfully.',
            'data' => $tokenDTO->toArray(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Login and get a JWT token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromArray($request->validated());

        $tokenDTO = $this->authService->login($dto);

        if (!$tokenDTO) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'message' => 'Login successful.',
            'data' => $tokenDTO->toArray(),
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Get the authenticated user profile.
     */
    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }
}

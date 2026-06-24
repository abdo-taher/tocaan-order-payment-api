<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterUserDTO;
use App\DTOs\TokenDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user and return a JWT token.
     */
    public function register(RegisterUserDTO $dto): TokenDTO
    {
        $user = $this->userRepository->create($dto->toArray());

        $token = auth()->login($user);

        return $this->buildToken($token);
    }

    /**
     * Attempt login with credentials.
     */
    public function login(LoginDTO $dto): ?TokenDTO
    {
        $token = auth()->attempt($dto->toArray());

        if (!$token) {
            return null;
        }

        return $this->buildToken($token);
    }

    /**
     * Logout the current user (invalidate the token).
     */
    public function logout(): void
    {
        auth()->logout();
    }

    /**
     * Get the authenticated user.
     */
    public function me(): User
    {
        return auth()->user();
    }

    /**
     * Build a TokenDTO from a raw JWT string.
     */
    private function buildToken(string $token): TokenDTO
    {
        return new TokenDTO(
            accessToken: $token,
            tokenType: 'bearer',
            expiresIn: (int) (auth()->factory()->getTTL() * 60),
        );
    }
}

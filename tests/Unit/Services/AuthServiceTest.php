<?php

namespace Tests\Unit\Services;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterUserDTO;
use App\DTOs\TokenDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
    }

    public function test_register_creates_user_and_returns_token_dto(): void
    {
        $dto = new RegisterUserDTO(
            name: 'Jane Doe',
            email: 'jane@example.com',
            password: 'password123',
        );

        $result = $this->authService->register($dto);

        $this->assertInstanceOf(TokenDTO::class, $result);
        $this->assertEquals('bearer', $result->tokenType);
        $this->assertNotEmpty($result->accessToken);
        $this->assertGreaterThan(0, $result->expiresIn);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);
    }

    public function test_login_returns_token_dto_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $dto = new LoginDTO(
            email: 'jane@example.com',
            password: 'password123',
        );

        $result = $this->authService->login($dto);

        $this->assertInstanceOf(TokenDTO::class, $result);
        $this->assertEquals('bearer', $result->tokenType);
        $this->assertNotEmpty($result->accessToken);
    }

    public function test_login_returns_null_for_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $dto = new LoginDTO(
            email: 'jane@example.com',
            password: 'wrongpassword',
        );

        $result = $this->authService->login($dto);

        $this->assertNull($result);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        auth()->login($user);

        $result = $this->authService->me();

        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->email, $result->email);
    }

    public function test_register_uses_repository_to_create_user(): void
    {
        $mockRepo = $this->mock(UserRepositoryInterface::class);

        $mockRepo->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
            ])
            ->andReturn(User::factory()->make([
                'id' => 1,
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]));

        $service = app(AuthService::class);

        $dto = new RegisterUserDTO(
            name: 'Test User',
            email: 'test@example.com',
            password: 'password123',
        );

        $result = $service->register($dto);

        $this->assertInstanceOf(TokenDTO::class, $result);
    }

    public function test_token_dto_to_array_returns_correct_structure(): void
    {
        $token = new TokenDTO(
            accessToken: 'abc.def.ghi',
            tokenType: 'bearer',
            expiresIn: 3600,
        );

        $array = $token->toArray();

        $this->assertEquals([
            'access_token' => 'abc.def.ghi',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ], $array);
    }
}

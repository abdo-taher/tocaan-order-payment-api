<?php

namespace App\Providers;

use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository bindings.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        OrderRepositoryInterface::class => OrderRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

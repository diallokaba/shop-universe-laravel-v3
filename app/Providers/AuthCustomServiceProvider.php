<?php

namespace App\Providers;

use App\Services\AuthenticationPassport;
use App\Services\AuthenticationSanctum;
use App\Services\AuthenticationServiceInterface;
use Illuminate\Support\ServiceProvider;

class AuthCustomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthenticationServiceInterface::class, AuthenticationPassport::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

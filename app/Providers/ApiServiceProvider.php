<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Afl\AflService;
use App\Services\Afl\Utils\Analyzer;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(AflService::class);
        $this->app->singleton(Analyzer::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}

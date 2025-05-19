<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RapidApi\BaseballApiService;
use App\Services\RapidApi\FootballApiService;
use App\Services\RapidApi\BasketballApiService;

class RapidApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(BaseballApiService::class);
        $this->app->singleton(FootballApiService::class);
        $this->app->singleton(BasketballApiService::class);
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

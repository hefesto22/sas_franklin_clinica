<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\CambioEvento;
use App\Observers\CambioEventoObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CambioEvento::observe(CambioEventoObserver::class);
    }
}

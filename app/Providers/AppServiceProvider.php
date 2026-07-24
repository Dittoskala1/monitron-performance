<?php

namespace App\Providers;

use App\Models\Alat;                 
use App\Observers\AlatObserver;         
use Illuminate\Support\ServiceProvider;

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
        Alat::observe(AlatObserver::class);
    }
}

<?php

namespace App\Providers;

use App\Models\Alat;                 
use App\Models\LogHarian;
use App\Observers\AlatObserver;         
use App\Observers\LogHarianObserver;
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
        // ⚠️ BARU: sinkronisasi otomatis Alat::kondisi_terkini dari entri
        // log_harian paling baru, tiap kali ada log baru/edit.
        LogHarian::observe(LogHarianObserver::class);
    }

}
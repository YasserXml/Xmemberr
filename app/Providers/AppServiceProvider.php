<?php

namespace App\Providers;

use App\Models\Barangmasuk;
use App\Observers\BarangmasukObserver;
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
        Barangmasuk::observe(BarangmasukObserver::class);
    }
}

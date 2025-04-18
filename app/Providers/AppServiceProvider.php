<?php

namespace App\Providers;

use App\Models\Barangmasuk;
use App\Models\Transaksi;
use App\Observers\BarangmasukObserver;
use App\Observers\TransaksiObserver;
use Illuminate\View\View;
use Filament\Facades\Filament;
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
        Transaksi::observe(TransaksiObserver::class);
        \Carbon\Carbon::setLocale('id');
    }
    }


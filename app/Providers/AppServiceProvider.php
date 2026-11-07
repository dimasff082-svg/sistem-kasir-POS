<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
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
        View::composer('*', function ($view) {
            $view->with([
                'storeSettings' => Setting::allMapped(),
                'storeName' => Setting::getStoreName(),
                'storeLogo' => Setting::getLogoUrl(),
                'storePhone' => Setting::getStorePhone(),
                'storeAddress' => Setting::getStoreAddress(),
                'storeEmail' => Setting::getStoreEmail(),
                'storeHours' => Setting::getStoreHours(),
            ]);
        });
    }
}

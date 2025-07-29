<?php

namespace App\Providers;

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
        config([
            'laravellocalization.supportedLocales' => [
                'en' => [
                    'name' => 'English',
                    'script' => 'Latn',
                    'native' => 'English',
                    'regional' => 'en_US'
                ],
                'tr' => [
                    'name' => 'Turkish',
                    'script' => 'Latn',
                    'native' => 'Türkçe',
                    'regional' => 'tr_TR'
                ],
            ]
        ]);
    }
}

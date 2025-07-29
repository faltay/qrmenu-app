<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ]
], function() {
    
    // Ana sayfa
    Route::get('/', function () {
        return view('welcome');
    })->name('home');
    
    // Test sayfası
    Route::get('/test', function () {
        return "QR Menu App is working! Current locale: " . app()->getLocale();
    })->name('test');
    
});

// Dil route tanımları (opsiyonel - gelişmiş kullanım için)
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ]
], function() {
    
    // Çevrilebilir route'lar (gelecekte)
    Route::get(LaravelLocalization::transRoute('routes.about'), function() {
        return 'About page';
    })->name('about');
    
});

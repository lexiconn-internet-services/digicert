<?php

namespace LexiConnInternetServices\DigiCert;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use LexiConnInternetServices\DigiCert\Client;

/**
 * Class DigiCertServiceProvider
 *
 * @package LexiConnInternetServices\DigiCert\ServiceProvider
 */
class DigiCertServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/lang', 'digicert');
        $this->publishes([
            __DIR__.'/config/digicert.php' => config_path('digicert.php'),
            __DIR__.'/lang'                => resource_path('lang/vendor/digicert'),
        ]);
    }
    
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/digicert.php',
            'digicert'
        );
        $this->app->singleton(RateLimterService::class, function ($app) {
            return new RateLimterService(config('digicert.rate_limit', 950), config('digicert.rate_interval', 300));
        });
    }
}

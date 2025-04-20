<?php

namespace Gcorpllc\Paypey\Providers;

use Illuminate\Support\ServiceProvider;

class PaypeyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('paypey', function () {

             return new \Gcorpllc\Paypey\Payment\PaymentManager();

        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'paypey');
        $this->mergeConfigFrom(__DIR__.'/../../config/paypey.php', 'paypey');

        $this->publishes([__DIR__.'/../../config/paypey.php' => config_path('paypey.php'),], 'paypey-config');
    }
}

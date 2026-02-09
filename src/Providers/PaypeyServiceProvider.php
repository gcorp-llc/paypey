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
        $this->mergeConfigFrom(__DIR__.'/../../config/paypey.php', 'paypey');
        $this->app->singleton('paypey', function () {

             return new \Gcorpllc\Paypey\Payment\PaymentManager();

        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'paypey');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/paypey.php' => config_path('paypey.php'),
            ], 'paypey-config');

            $this->publishes([
                __DIR__ . '/../../lang' => $this->app->langPath('vendor/paypey'),
            ], 'paypey-translations');

            $this->publishes([
                __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            ], 'paypey-migrations');
        }
    }
}

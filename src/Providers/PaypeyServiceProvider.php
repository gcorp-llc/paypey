<?php

namespace Gcorpllc\Paypey\Providers;

use Gcorpllc\Paypey\PaypeyManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PaypeyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/paypey.php', 'paypey');

        $this->app->singleton('paypey', function ($app) {
            return new PaypeyManager($app);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/paypey.php' => config_path('paypey.php'),
            ], 'paypey-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            ], 'paypey-migrations');

            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        $this->registerSandboxRoutes();
    }

    protected function registerSandboxRoutes(): void
    {
        Route::get('/paypey/sandbox/redirect/{authority}', function (string $authority) {
            $callbackUrl = request('callback_url', '/');

            return "<html>
<head><title>Paypey Sandbox Gateway</title></head>
<body style='font-family: sans-serif; text-align: center; padding-top: 50px;'>
    <h2>Paypey Sandbox Payment Gateway</h2>
    <p>Transaction Authority: <strong>{$authority}</strong></p>
    <p>Select outcome to proceed:</p>
    <form action='{$callbackUrl}' method='GET' style='margin-bottom: 10px;'>
        <input type='hidden' name='Authority' value='{$authority}'>
        <input type='hidden' name='Status' value='OK'>
        <button type='submit' style='background: #28a745; color: white; border: none; padding: 10px 20px; font-size: 16px; cursor: pointer; border-radius: 5px;'>Complete Payment (Success)</button>
    </form>
    <form action='{$callbackUrl}' method='GET'>
        <input type='hidden' name='Authority' value='{$authority}'>
        <input type='hidden' name='Status' value='NOK'>
        <button type='submit' style='background: #dc3545; color: white; border: none; padding: 10px 20px; font-size: 16px; cursor: pointer; border-radius: 5px;'>Cancel Payment (Failed)</button>
    </form>
</body>
</html>";
        })->name('paypey.sandbox.redirect');
    }
}

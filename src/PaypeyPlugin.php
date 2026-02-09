<?php

namespace Gcorpllc\Paypey;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Gcorpllc\Paypey\Filament\Resources\PaymentGatewayResource;
use Gcorpllc\Paypey\Filament\Resources\PaymentTransactionResource;

class PaypeyPlugin implements Plugin
{
    public function getId(): string
    {
        return 'paypey';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PaymentGatewayResource::class,
            PaymentTransactionResource::class,
        ])->widgets([
            \Gcorpllc\Paypey\Filament\Widgets\PaymentStatsWidget::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}

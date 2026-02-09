<?php

namespace Gcorpllc\Paypey\Filament\Resources\PaymentGatewayResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Gcorpllc\Paypey\Filament\Resources\PaymentGatewayResource;

class ListPaymentGateways extends ListRecords
{
    protected static string $resource = PaymentGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

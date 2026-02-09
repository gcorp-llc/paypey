<?php

namespace Gcorpllc\Paypey\Filament\Resources\PaymentTransactionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Gcorpllc\Paypey\Filament\Resources\PaymentTransactionResource;

class ListPaymentTransactions extends ListRecords
{
    protected static string $resource = PaymentTransactionResource::class;
}

<?php

namespace Gcorpllc\Paypey\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Gcorpllc\Paypey\Models\PaymentTransaction;

class PaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPaid = PaymentTransaction::where('status', 'paid')->sum('amount');
        $countSuccess = PaymentTransaction::where('status', 'paid')->count();
        $countPending = PaymentTransaction::where('status', 'pending')->count();

        return [
            Stat::make(__('paypey::messages.total_sales'), number_format($totalPaid))
                ->description(__('paypey::messages.successful_transactions'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make(__('paypey::messages.success_count'), $countSuccess),
            Stat::make(__('paypey::messages.pending_count'), $countPending),
        ];
    }
}

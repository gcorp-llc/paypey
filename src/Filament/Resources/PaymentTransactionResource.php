<?php

namespace Gcorpllc\Paypey\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Gcorpllc\Paypey\Models\PaymentTransaction;

class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    public static function getNavigationGroup(): ?string
    {
        return __('paypey::messages.payment_management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('driver'),
                Forms\Components\TextInput::make('amount'),
                Forms\Components\TextInput::make('currency'),
                Forms\Components\TextInput::make('status'),
                Forms\Components\TextInput::make('transaction_id'),
                Forms\Components\KeyValue::make('metadata'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('driver')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money(fn ($record) => $record->currency)->sortable(),
                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'failed',
                        'warning' => 'pending',
                        'success' => 'paid',
                    ]),
                Tables\Columns\TextColumn::make('transaction_id')->searchable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTransactions::route('/'),
        ];
    }
}

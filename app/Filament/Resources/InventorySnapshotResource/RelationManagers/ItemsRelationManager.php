<?php

namespace App\Filament\Resources\InventorySnapshotResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'ردیف‌های موجودی';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_code')
            ->columns([
                Tables\Columns\TextColumn::make('product_code')->label('کد کالا')->searchable(),
                Tables\Columns\TextColumn::make('description')->label('شرح')->limit(40),
                Tables\Columns\TextColumn::make('unit')->label('واحد'),
                Tables\Columns\TextColumn::make('quantity')->label('موجودی')->numeric(decimalPlaces: 0),
                Tables\Columns\TextColumn::make('purchase_price')->label('قیمت خرید')->money('IRR', locale: 'fa'),
            ])
            ->defaultSort('product_code')
            ->paginated([25, 50, 100]);
    }
}
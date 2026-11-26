<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventorySnapshotResource\Pages;
use App\Models\InventorySnapshot;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventorySnapshotResource extends Resource
{
    protected static ?string $model = InventorySnapshot::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'تاریخچه موجودی';

    protected static ?string $modelLabel = 'Snapshot';

    protected static ?string $navigationGroup = 'انبار';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('inventory.history') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('original_filename')->label('فایل'),
                Tables\Columns\TextColumn::make('items_count')->label('کل کالا'),
                Tables\Columns\TextColumn::make('in_stock_count')->label('موجود'),
                Tables\Columns\TextColumn::make('zero_stock_count')->label('صفر'),
                Tables\Columns\TextColumn::make('recorded_at')->label('تاریخ ثبت')->dateTime(),
            ])
            ->defaultSort('recorded_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventorySnapshots::route('/'),
        ];
    }
}
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryLogResource\Pages;
use App\Models\DeliveryLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveryLogResource extends Resource
{
    protected static ?string $model = DeliveryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'تاریخچه ارسال';

    protected static ?string $navigationGroup = 'فروش';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('sales.notify') || $user->can('settings.manage'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->can('settings.manage')) {
            return $query;
        }

        return $query->where('user_id', auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salesFile.title')->label('فایل فروش'),
                Tables\Columns\TextColumn::make('customer.mobile')->label('مشتری'),
                Tables\Columns\TextColumn::make('channel')->label('کانال')->badge(),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')->badge(),
                Tables\Columns\TextColumn::make('error_message')->label('خطا')->limit(50)->toggleable(),
                Tables\Columns\TextColumn::make('attempts')->label('تلاش'),
                Tables\Columns\TextColumn::make('created_at')->label('زمان')->shamsiDateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryLogs::route('/'),
        ];
    }
}
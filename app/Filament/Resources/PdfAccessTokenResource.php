<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PdfAccessTokenResource\Pages;
use App\Models\PdfAccessToken;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PdfAccessTokenResource extends Resource
{
    protected static ?string $model = PdfAccessToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'لینک‌های PDF';

    protected static ?string $navigationGroup = 'فروش';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salesFile.title')->label('لیست فروش')->limit(30),
                Tables\Columns\TextColumn::make('token')->label('توکن')->limit(12)->copyable(),
                Tables\Columns\TextColumn::make('expires_at')->label('انقضا')->shamsiDateTime(),
                Tables\Columns\TextColumn::make('view_count')->label('بازدید'),
                Tables\Columns\TextColumn::make('download_count')->label('دانلود'),
                Tables\Columns\IconColumn::make('revoked_at')
                    ->label('ابطال‌شده')
                    ->boolean()
                    ->getStateUsing(fn (PdfAccessToken $record) => $record->revoked_at !== null),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('revoke')
                    ->label('ابطال')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PdfAccessToken $record) => $record->revoked_at === null)
                    ->requiresConfirmation()
                    ->action(fn (PdfAccessToken $record) => $record->update(['revoked_at' => now()])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPdfAccessTokens::route('/'),
        ];
    }
}
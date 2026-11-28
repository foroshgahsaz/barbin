<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessengerResource\Pages;
use App\Models\Messenger;
use App\Services\Messengers\MessengerManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessengerResource extends Resource
{
    protected static ?string $model = Messenger::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationLabel = 'پیام‌رسان‌ها';

    protected static ?string $navigationGroup = 'تنظیمات';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('messengers.manage') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('driver')->label('درایور')->options([
                'telegram' => 'تلگرام',
                'bale' => 'بله',
                'rubika' => 'روبیکا',
            ])->required()->live(),
            Forms\Components\TextInput::make('name')->label('نام')->required(),
            Forms\Components\TextInput::make('bot_token')->label('Bot Token')->password()->revealable()->required()
                ->helperText(fn (Get $get): string => match ($get('driver')) {
                    'telegram' => 'از @BotFather — مثال: 123456789:AAH…',
                    'bale' => 'توکن ربات از پنل بله',
                    default => '',
                }),
            Forms\Components\TextInput::make('channel_id')->label('شناسه کانال')
                ->helperText(fn (Get $get): string => match ($get('driver')) {
                    'telegram' => 'بات را ادمین کانال کنید. @username کانال یا عددی مثل -1001234567890',
                    'bale' => 'شناسه عددی کانال بله',
                    default => 'در صورت نیاز',
                }),
            Forms\Components\TextInput::make('channel_name')->label('نام کانال (یادداشت)'),
            Forms\Components\Toggle::make('is_active')->label('فعال'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام'),
                Tables\Columns\TextColumn::make('driver')->label('درایور')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'telegram' => 'تلگرام',
                        'bale' => 'بله',
                        'rubika' => 'روبیکا',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('فعال'),
                Tables\Columns\TextColumn::make('last_success_at')->shamsiDateTime()->label('آخرین موفق'),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('تست')
                    ->action(function (Messenger $record) {
                        $result = app(MessengerManager::class)->driver($record)->testConnection();
                        Notification::make()
                            ->title($result['success'] ?? false ? 'موفق' : 'ناموفق')
                            ->body($result['error'] ?? 'OK')
                            ->{$result['success'] ?? false ? 'success' : 'danger'}()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMessengers::route('/'),
        ];
    }
}
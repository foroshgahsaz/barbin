<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessengerResource\Pages;
use App\Models\Messenger;
use App\Services\Logs\AdminErrorLogger;
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

    protected static ?string $modelLabel = 'پیام‌رسان';

    protected static ?string $pluralModelLabel = 'پیام‌رسان‌ها';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('messengers.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
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
            Forms\Components\TextInput::make('bot_token')->label('Bot Token')->password()->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(fn (Get $get, string $operation): string => match (true) {
                    $operation === 'edit' => 'برای نگه‌داشتن توکن فعلی خالی بگذارید.',
                    $get('driver') === 'telegram' => 'از @BotFather — مثال: 123456789:AAH…',
                    $get('driver') === 'bale' => 'توکن ربات از پنل بله',
                    default => '',
                }),
            Forms\Components\TextInput::make('channel_id')->label('شناسه کانال')
                ->helperText(fn (Get $get): string => match ($get('driver')) {
                    'telegram' => 'بات را ادمین کانال کنید. @username کانال یا عددی مثل -1001234567890',
                    'bale' => 'شناسه عددی کانال بله',
                    default => 'در صورت نیاز',
                }),
            Forms\Components\TextInput::make('channel_name')->label('نام کانال (یادداشت)'),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
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
                Tables\Columns\TextColumn::make('last_error')->label('آخرین خطا')->limit(40)->tooltip(fn (?string $state) => $state),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش'),
                Tables\Actions\Action::make('test')
                    ->label('تست')
                    ->action(function (Messenger $record) {
                        $result = app(MessengerManager::class)->driver($record)->testConnection();

                        if ($result['success'] ?? false) {
                            $record->update([
                                'last_success_at' => now(),
                                'last_error' => null,
                            ]);
                        } else {
                            $error = (string) ($result['error'] ?? 'خطای ناشناخته');
                            $record->update(['last_error' => $error]);
                            AdminErrorLogger::record('messenger.test', $error, context: [
                                'messenger_id' => $record->id,
                                'driver' => $record->driver,
                            ]);
                        }

                        Notification::make()
                            ->title($result['success'] ?? false ? 'موفق' : 'ناموفق')
                            ->body($result['error'] ?? 'OK')
                            ->{$result['success'] ?? false ? 'success' : 'danger'}()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMessengers::route('/'),
        ];
    }
}
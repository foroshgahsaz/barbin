<?php

namespace App\Filament\Resources\MessengerResource\Pages;

use App\Filament\Resources\MessengerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMessengers extends ManageRecords
{
    protected static string $resource = MessengerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('ایجاد پیام‌رسان')
                ->modalHeading('پیام‌رسان جدید')
                ->successNotificationTitle('پیام‌رسان ذخیره شد'),
        ];
    }
}
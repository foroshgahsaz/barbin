<?php

namespace App\Filament\Resources\ProductImageQueueResource\Pages;

use App\Filament\Resources\ProductImageQueueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductImageQueue extends EditRecord
{
    protected static string $resource = ProductImageQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ProductImageQueueResource::getUrl('index');
    }
}
<?php

namespace App\Filament\Resources\CatalogProductResource\Pages;

use App\Filament\Resources\CatalogProductResource;
use App\Filament\Resources\ProductImageQueueResource;
use App\Services\Catalog\CatalogProductImageService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatalogProduct extends EditRecord
{
    protected static string $resource = CatalogProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $upload = $this->data['new_upload'] ?? null;
        $path = is_array($upload) ? ($upload[0] ?? null) : $upload;

        if (! filled($path)) {
            return;
        }

        /** @var \App\Models\CatalogProduct $record */
        $record = $this->record->fresh();

        app(CatalogProductImageService::class)->attachFromStoredPath(
            $record,
            (string) $path,
            (int) auth()->id(),
        );
    }

    protected function getRedirectUrl(): string
    {
        /** @var \App\Models\CatalogProduct $record */
        $record = $this->record->fresh();

        if ($record->product_image_id === null) {
            return ProductImageQueueResource::getUrl('index');
        }

        return CatalogProductResource::getUrl('index');
    }
}
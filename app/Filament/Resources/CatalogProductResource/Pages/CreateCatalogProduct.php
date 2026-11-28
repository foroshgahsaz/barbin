<?php

namespace App\Filament\Resources\CatalogProductResource\Pages;

use App\Filament\Resources\CatalogProductResource;
use App\Services\Catalog\CatalogProductImageService;
use App\Support\ProductCode;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCatalogProduct extends CreateRecord
{
    protected static string $resource = CatalogProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $code = ProductCode::normalize($data['product_code'] ?? null);
        if ($code === null) {
            throw new \InvalidArgumentException('کد کالا نامعتبر است.');
        }

        $upload = $data['new_upload'] ?? null;
        $path = is_array($upload) ? ($upload[0] ?? null) : $upload;

        $record = static::getModel()::query()->create([
            'product_code' => $code,
            'description' => $data['description'] ?? null,
            'unit' => $data['unit'] ?? null,
            'product_image_id' => null,
        ]);

        if (filled($path)) {
            app(CatalogProductImageService::class)->attachFromStoredPath(
                $record,
                (string) $path,
                (int) auth()->id(),
            );
        } else {
            Notification::make()
                ->title('بدون تصویر ذخیره شد — در صف «تصاویر محصولات» است')
                ->info()
                ->send();
        }

        return $record->fresh();
    }

    protected function getRedirectUrl(): string
    {
        $record = $this->record;

        if ($record->product_image_id !== null) {
            return CatalogProductResource::getUrl('index');
        }

        return \App\Filament\Resources\ProductImageQueueResource::getUrl('index');
    }
}
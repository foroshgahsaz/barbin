<?php

namespace App\Filament\Resources\ProductImageResource\Pages;

use App\Filament\Resources\ProductImageResource;
use App\Models\ProductImageCode;
use App\Services\ProductImages\ProductImageService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductImage extends EditRecord
{
    protected static string $resource = ProductImageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var \App\Models\ProductImage $record */
        $record = $this->record;
        $data['product_codes'] = $record->codes()->pluck('product_code')->all();

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var \App\Models\ProductImage $record */
        $record = $this->record;
        $codes = $this->data['product_codes'] ?? [];

        ProductImageCode::query()->where('product_image_id', $record->id)->delete();
        app(ProductImageService::class)->attachCodes($record, $codes);
    }
}
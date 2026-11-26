<?php

namespace App\Filament\Resources\ProductImageResource\Pages;

use App\Filament\Resources\ProductImageResource;
use App\Services\ProductImages\ProductImageService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class CreateProductImage extends CreateRecord
{
    protected static string $resource = ProductImageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $path = is_array($data['upload'] ?? null) ? ($data['upload'][0] ?? null) : ($data['upload'] ?? null);
        $absolute = \Illuminate\Support\Facades\Storage::disk('local')->path((string) $path);

        $file = new UploadedFile($absolute, basename($absolute), null, null, true);

        $image = app(ProductImageService::class)->store(
            $file,
            auth()->id(),
            $data['title'] ?? null,
        );

        app(ProductImageService::class)->attachCodes(
            $image,
            $data['product_codes'] ?? [],
        );

        return $image;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
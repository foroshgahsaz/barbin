<?php

namespace App\Filament\Resources\InventoryImportBatchResource\Pages;

use App\Filament\Resources\InventoryImportBatchResource;
use App\Services\Inventory\InventoryImportService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInventoryImportBatch extends CreateRecord
{
    protected static string $resource = InventoryImportBatchResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $path = is_array($data['upload'] ?? null) ? ($data['upload'][0] ?? null) : ($data['upload'] ?? null);

        if (! $path) {
            Notification::make()->title('فایل انتخاب نشده است')->danger()->send();
            throw new \RuntimeException('فایل انتخاب نشده است');
        }

        return app(InventoryImportService::class)->createPreview(
            auth()->user(),
            basename($path),
            $path,
        );
    }
}
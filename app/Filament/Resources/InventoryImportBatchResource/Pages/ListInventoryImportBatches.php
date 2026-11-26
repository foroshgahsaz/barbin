<?php

namespace App\Filament\Resources\InventoryImportBatchResource\Pages;

use App\Filament\Resources\InventoryImportBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryImportBatches extends ListRecords
{
    protected static string $resource = InventoryImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('بارگذاری جدید'),
        ];
    }
}
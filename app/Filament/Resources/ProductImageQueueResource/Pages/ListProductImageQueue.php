<?php

namespace App\Filament\Resources\ProductImageQueueResource\Pages;

use App\Filament\Resources\ProductImageQueueResource;
use Filament\Resources\Pages\ListRecords;

class ListProductImageQueue extends ListRecords
{
    protected static string $resource = ProductImageQueueResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
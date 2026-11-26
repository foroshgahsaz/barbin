<?php

namespace App\Filament\Resources\SalesFileResource\Pages;

use App\Filament\Resources\SalesFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesFiles extends ListRecords
{
    protected static string $resource = SalesFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('بارگذاری فایل فروش'),
        ];
    }
}
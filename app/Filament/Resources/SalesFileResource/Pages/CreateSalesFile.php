<?php

namespace App\Filament\Resources\SalesFileResource\Pages;

use App\Filament\Resources\SalesFileResource;
use App\Services\Sales\SalesFileImportService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalesFile extends CreateRecord
{
    protected static string $resource = SalesFileResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $path = is_array($data['upload'] ?? null) ? ($data['upload'][0] ?? null) : ($data['upload'] ?? null);

        return app(SalesFileImportService::class)->import(
            auth()->user(),
            $data['title'],
            basename((string) $path),
            (string) $path,
        );
    }
}
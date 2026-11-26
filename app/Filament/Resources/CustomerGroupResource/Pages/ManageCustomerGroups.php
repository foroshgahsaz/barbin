<?php

namespace App\Filament\Resources\CustomerGroupResource\Pages;

use App\Filament\Resources\CustomerGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;

class ManageCustomerGroups extends ManageRecords
{
    protected static string $resource = CustomerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_user_id'] = auth()->id();
        $data['slug'] = Str::slug($data['name']).'-'.auth()->id();

        return $data;
    }
}
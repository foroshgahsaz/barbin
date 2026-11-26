<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['roles']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! empty($this->data['roles'])) {
            $this->record->syncRoles($this->data['roles']);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['roles']);

        return $data;
    }

    protected function afterSave(): void
    {
        if (isset($this->data['roles'])) {
            $this->record->syncRoles($this->data['roles']);
        }
    }
}
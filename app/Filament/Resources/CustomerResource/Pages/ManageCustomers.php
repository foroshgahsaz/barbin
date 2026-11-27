<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Services\Customers\CustomerImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageCustomers extends ManageRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import_excel')
                ->label('Import Excel')
                ->form([
                    FileUpload::make('file')->disk('local')->directory('customer-imports')->required(),
                ])
                ->action(function (array $data) {
                    $path = is_array($data['file'] ?? null) ? ($data['file'][0] ?? null) : ($data['file'] ?? null);
                    $result = app(CustomerImportService::class)->importFromPath(
                        auth()->user(),
                        Storage::disk('local')->path((string) $path),
                    );
                    Notification::make()
                        ->title("وارد شد: {$result['imported']} — رد شد: {$result['skipped']}")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_user_id'] = auth()->id();

        return $data;
    }
}
<?php

namespace App\Filament\Resources;

use App\Enums\ImportBatchStatus;
use App\Filament\Resources\InventoryImportBatchResource\Pages;
use App\Models\InventoryImportBatch;
use App\Services\Inventory\InventoryImportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class InventoryImportBatchResource extends Resource
{
    protected static ?string $model = InventoryImportBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'بارگذاری موجودی';

    protected static ?string $modelLabel = 'فایل موجودی';

    protected static ?string $navigationGroup = 'انبار';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('inventory.upload') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('upload')
                ->label('فایل Excel موجودی')
                ->disk('local')
                ->directory('inventory-uploads')
                ->acceptedFileTypes([
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel',
                    'text/csv',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('original_filename')->label('نام فایل')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')->badge(),
                Tables\Columns\TextColumn::make('row_count')->label('ردیف‌ها'),
                Tables\Columns\TextColumn::make('duplicate_codes_count')->label('کد تکراری'),
                Tables\Columns\TextColumn::make('positive_stock_count')->label('موجود'),
                Tables\Columns\TextColumn::make('zero_stock_count')->label('صفر'),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ')->shamsiDateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('تأیید و ثبت Snapshot')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (InventoryImportBatch $record) => $record->status === ImportBatchStatus::Preview
                        && auth()->user()?->can('inventory.confirm'))
                    ->requiresConfirmation()
                    ->action(function (InventoryImportBatch $record) {
                        try {
                            app(InventoryImportService::class)->confirm($record);
                            Notification::make()->title('موجودی با موفقیت ثبت شد')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryImportBatches::route('/'),
            'create' => Pages\CreateInventoryImportBatch::route('/create'),
        ];
    }
}
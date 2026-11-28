<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesFileResource\Pages;
use App\Jobs\SendCustomerSmsJob;
use App\Models\Customer;
use App\Models\Messenger;
use App\Models\SalesFile;
use App\Services\Messengers\MessengerManager;
use App\Services\Pdf\SalesPdfGenerator;
use App\Services\Sales\SalesExcelExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Support\StoragePaths;
use Illuminate\Support\Facades\Storage;

class SalesFileResource extends Resource
{
    protected static ?string $model = SalesFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'فایل‌های فروش';

    protected static ?string $modelLabel = 'فایل فروش';

    protected static ?string $navigationGroup = 'فروش';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if ($user?->can('sales.view_all')) {
            return $query;
        }

        return $query->where('user_id', $user?->id);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('sales.view_own') || $user->can('sales.view_all'));
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('sales.upload') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('عنوان لیست')->required()->maxLength(191),
            Forms\Components\FileUpload::make('upload')
                ->label('فایل Excel فروش')
                ->disk('local')
                ->directory('sales-uploads')
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
                Tables\Columns\TextColumn::make('title')->label('عنوان')->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label('مدیر فروش')->toggleable(),
                Tables\Columns\TextColumn::make('product_count')->label('تعداد محصول'),
                Tables\Columns\TextColumn::make('processing_status')->label('وضعیت')->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                Tables\Columns\TextColumn::make('pdf_generated_at')->label('تولید PDF')->shamsiDateTime()->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ')->shamsiDateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('generate_pdf')
                    ->label('تولید PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn () => auth()->user()?->can('sales.pdf'))
                    ->action(function (SalesFile $record) {
                        try {
                            app(SalesPdfGenerator::class)->generate($record);
                            Notification::make()->title('PDF تولید شد')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('download_pdf')
                    ->label('دانلود PDF')
                    ->visible(fn (SalesFile $record) => $record->pdf_path && StoragePaths::exists($record->pdf_path))
                    ->action(fn (SalesFile $record) => StoragePaths::downloadResponse(
                        $record->pdf_path,
                        $record->title.'.pdf',
                    )),
                Tables\Actions\Action::make('export_excel')
                    ->label('Excel نهایی')
                    ->icon('heroicon-o-table-cells')
                    ->visible(fn () => auth()->user()?->can('sales.pdf'))
                    ->action(function (SalesFile $record) {
                        $path = app(SalesExcelExportService::class)->export($record);
                        Notification::make()->title('فایل Excel آماده شد')->success()->send();

                        return StoragePaths::downloadResponse($path, $record->title.'.xlsx');
                    }),
                Tables\Actions\Action::make('notify_sms')
                    ->label('پیامک مشتریان')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->visible(fn () => auth()->user()?->canNotifySales())
                    ->form(fn (SalesFile $record) => [
                        Forms\Components\Select::make('customer_ids')
                            ->label('مشتریان')
                            ->multiple()
                            ->options(
                                Customer::query()
                                    ->where('owner_user_id', $record->user_id)
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn ($c) => [$c->id => $c->full_name.' — '.$c->mobile])
                            )
                            ->required(),
                        Forms\Components\Toggle::make('allow_duplicate')->label('ارسال مجدد حتی اگر قبلاً ارسال شده'),
                    ])
                    ->requiresConfirmation()
                    ->action(function (SalesFile $record, array $data) {
                        foreach ($data['customer_ids'] as $customerId) {
                            SendCustomerSmsJob::dispatch(
                                $record->id,
                                auth()->id(),
                                (int) $customerId,
                                (bool) ($data['allow_duplicate'] ?? false),
                            );
                        }
                        Notification::make()
                            ->title('ارسال در صف قرار گرفت')
                            ->body('queue worker را اجرا کنید: php artisan queue:work')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('send_channel')
                    ->label('ارسال به کانال')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (SalesFile $record) => auth()->user()?->canNotifySales() && $record->pdf_path)
                    ->form([
                        Forms\Components\Select::make('messenger_id')
                            ->label('پیام‌رسان')
                            ->options(fn () => Messenger::query()->where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Textarea::make('message')->label('متن پیام')->required(),
                    ])
                    ->action(function (SalesFile $record, array $data) {
                        $messenger = Messenger::query()->findOrFail($data['messenger_id']);
                        $pdf = $record->pdf_path ? StoragePaths::absolutePath($record->pdf_path) : null;
                        $result = app(MessengerManager::class)->driver($messenger)
                            ->sendToChannel((string) $messenger->channel_id, $data['message'], $pdf);
                        if ($result['success'] ?? false) {
                            $messenger->update(['last_success_at' => now(), 'last_error' => null]);
                            Notification::make()->title('ارسال شد')->success()->send();
                        } else {
                            $messenger->update(['last_error' => $result['error'] ?? 'خطا']);
                            Notification::make()->title('خطا')->body($result['error'] ?? '')->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesFiles::route('/'),
            'create' => Pages\CreateSalesFile::route('/create'),
            'view' => Pages\ViewSalesFile::route('/{record}'),
        ];
    }
}
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesFileResource\Pages;
use App\Models\SalesFile;
use App\Services\Pdf\SalesPdfGenerator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                Tables\Columns\TextColumn::make('processing_status')->label('وضعیت')->badge(),
                Tables\Columns\TextColumn::make('pdf_generated_at')->label('تولید PDF')->dateTime()->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ')->dateTime(),
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
                    ->visible(fn (SalesFile $record) => $record->pdf_path && Storage::disk('local')->exists($record->pdf_path))
                    ->action(fn (SalesFile $record) => Storage::disk('local')->download(
                        $record->pdf_path,
                        $record->title.'.pdf',
                    )),
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
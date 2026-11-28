<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductImageQueueResource\Pages;
use App\Models\CatalogProduct;
use App\Services\Catalog\CatalogProductImageService;
use App\Support\ProductCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductImageQueueResource extends Resource
{
    protected static ?string $model = CatalogProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'تصاویر محصولات';

    protected static ?string $modelLabel = 'در انتظار تصویر';

    protected static ?string $pluralModelLabel = 'صف تصاویر';

    protected static ?string $navigationGroup = 'فروش';

    protected static ?string $slug = 'product-image-queue';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('images.manage') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('product_image_id')
            ->orderBy('product_code');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('product_code')
                ->label('کد کالا')
                ->required()
                ->maxLength(64)
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn ($state) => ProductCode::normalize($state)),
            Forms\Components\TextInput::make('description')->label('شرح کالا')->maxLength(255),
            Forms\Components\TextInput::make('unit')->label('واحد')->maxLength(32),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_code')->label('کد کالا')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('description')->label('شرح کالا')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('unit')->label('واحد')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('افزوده‌شده')->shamsiDateTime()->toggleable(),
            ])
            ->filters([])
            ->emptyStateHeading('همه کدها تصویر دارند')
            ->emptyStateDescription('کد جدید از موجودی یا افزودن دستی اینجا ظاهر می‌شود.')
            ->actions([
                Tables\Actions\Action::make('upload')
                    ->label('بارگذاری تصویر')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('upload')
                            ->label('تصویر')
                            ->image()
                            ->required()
                            ->disk('local')
                            ->directory('product-images-uploads')
                            ->maxSize(5120),
                    ])
                    ->action(function (CatalogProduct $record, array $data): void {
                        $path = is_array($data['upload'] ?? null) ? ($data['upload'][0] ?? null) : ($data['upload'] ?? null);
                        if (! filled($path)) {
                            Notification::make()->title('فایل انتخاب نشده است')->danger()->send();

                            return;
                        }

                        try {
                            app(CatalogProductImageService::class)->attachFromStoredPath(
                                $record,
                                (string) $path,
                                (int) auth()->id(),
                            );
                            Notification::make()->title('تصویر ثبت شد و به لیست محصولات منتقل شد')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\EditAction::make()->label('ویرایش کد/شرح'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('افزودن کد (بدون تصویر)')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['product_code'] = ProductCode::normalize($data['product_code'] ?? null);

                        return $data;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductImageQueue::route('/'),
            'edit' => Pages\EditProductImageQueue::route('/{record}/edit'),
        ];
    }
}
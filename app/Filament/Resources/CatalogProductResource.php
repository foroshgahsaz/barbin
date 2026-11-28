<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogProductResource\Pages;
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

class CatalogProductResource extends Resource
{
    protected static ?string $model = CatalogProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'محصولات';

    protected static ?string $modelLabel = 'محصول';

    protected static ?string $pluralModelLabel = 'محصولات';

    protected static ?string $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('images.manage') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('product_image_id')
            ->with('image')
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
            Forms\Components\FileUpload::make('new_upload')
                ->label('تصویر')
                ->image()
                ->disk('local')
                ->directory('product-images-uploads')
                ->maxSize(5120)
                ->helperText('برای جایگزینی تصویر، فایل جدید انتخاب کنید.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image.thumbnail_path')
                    ->label('تصویر')
                    ->disk('local')
                    ->defaultImageUrl(fn () => null),
                Tables\Columns\TextColumn::make('product_code')->label('کد کالا')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('description')->label('شرح کالا')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('unit')->label('واحد')->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('remove_image')
                    ->label('حذف تصویر')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('حذف تصویر')
                    ->modalDescription('این کد دوباره در صف «تصاویر محصولات» قرار می‌گیرد.')
                    ->action(function (CatalogProduct $record): void {
                        app(CatalogProductImageService::class)->detachImage($record);
                        Notification::make()->title('تصویر حذف شد؛ کد به صف بارگذاری برگشت')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make()->label('حذف از کاتالوگ'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogProducts::route('/'),
            'create' => Pages\CreateCatalogProduct::route('/create'),
            'edit' => Pages\EditCatalogProduct::route('/{record}/edit'),
        ];
    }
}
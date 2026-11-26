<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductImageResource\Pages;
use App\Models\ProductImage;
use App\Services\ProductImages\ProductImageService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductImageResource extends Resource
{
    protected static ?string $model = ProductImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'تصاویر محصولات';

    protected static ?string $navigationGroup = 'فروش';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('images.manage') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('codes');

        if (auth()->user()?->can('settings.manage')) {
            return $query;
        }

        return $query->where('owner_user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('عنوان')->maxLength(191),
            Forms\Components\FileUpload::make('upload')
                ->label('تصویر')
                ->image()
                ->required(fn (?ProductImage $record) => $record === null)
                ->disk('local')
                ->directory('product-images-uploads')
                ->maxSize(5120),
            Forms\Components\TagsInput::make('product_codes')
                ->label('کدهای متصل')
                ->placeholder('کد کالا')
                ->helperText('یک تصویر می‌تواند به چند کد متصل شود.'),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')->label('تصویر')->disk('local'),
                Tables\Columns\TextColumn::make('title')->label('عنوان')->searchable(),
                Tables\Columns\TextColumn::make('codes.product_code')->label('کدها')->badge()->limit(30),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('فعال'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductImages::route('/'),
            'create' => Pages\CreateProductImage::route('/create'),
            'edit' => Pages\EditProductImage::route('/{record}/edit'),
        ];
    }
}
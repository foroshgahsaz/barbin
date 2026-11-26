<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerGroupResource\Pages;
use App\Models\CustomerGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
class CustomerGroupResource extends Resource
{
    protected static ?string $model = CustomerGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'گروه مشتریان';

    protected static ?string $navigationGroup = 'فروش';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (auth()->user()?->can('customers.manage_all')) {
            return $query;
        }

        return $query->where('owner_user_id', auth()->id());
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('customers.manage_own') || auth()->user()?->can('customers.manage_all');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('نام گروه')->required(),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable(),
                Tables\Columns\TextColumn::make('customers_count')->counts('customers')->label('تعداد مشتری'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('فعال'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCustomerGroups::route('/'),
        ];
    }
}
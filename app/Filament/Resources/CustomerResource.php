<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'مشتریان';

    protected static ?string $navigationGroup = 'فروش';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if ($user?->can('customers.manage_all')) {
            return $query;
        }

        return $query->where('owner_user_id', $user?->id);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('customers.manage_own') || $user->can('customers.manage_all'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('first_name')->label('نام')->required(),
            Forms\Components\TextInput::make('last_name')->label('نام خانوادگی')->required(),
            Forms\Components\TextInput::make('mobile')->label('موبایل')->required()->tel(),
            Forms\Components\Select::make('customer_group_id')->label('گروه')->relationship('group', 'name'),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
            Forms\Components\Toggle::make('notify_consent')->label('رضایت اطلاع‌رسانی')->default(true),
            Forms\Components\Textarea::make('notes')->label('توضیحات')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('نام'),
                Tables\Columns\TextColumn::make('last_name')->label('نام خانوادگی'),
                Tables\Columns\TextColumn::make('mobile')->label('موبایل'),
                Tables\Columns\TextColumn::make('group.name')->label('گروه'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCustomers::route('/'),
        ];
    }
}
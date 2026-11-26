<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'کاربران';

    protected static ?string $navigationGroup = 'تنظیمات';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('users.manage') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('نام')->required(),
            Forms\Components\TextInput::make('email')->label('ایمیل')->email()->required(),
            Forms\Components\TextInput::make('mobile')->label('موبایل')->tel(),
            Forms\Components\TextInput::make('password')
                ->label('رمز')
                ->password()
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create'),
            Forms\Components\Select::make('roles')
                ->label('نقش')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload(),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام'),
                Tables\Columns\TextColumn::make('email')->label('ایمیل'),
                Tables\Columns\TextColumn::make('roles.name')->label('نقش')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('فعال'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
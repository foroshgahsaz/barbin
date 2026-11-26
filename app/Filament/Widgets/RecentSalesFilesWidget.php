<?php

namespace App\Filament\Widgets;

use App\Models\SalesFile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentSalesFilesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'چهار فایل فروش اخیر';

    public static function canView(): bool
    {
        return auth()->user()?->can('sales.view_own') ?? false;
    }

    public function table(Table $table): Table
    {
        $query = SalesFile::query()->latest()->limit(4);
        if (! auth()->user()?->can('sales.view_all')) {
            $query->where('user_id', auth()->id());
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان'),
                Tables\Columns\TextColumn::make('product_count')->label('محصولات'),
                Tables\Columns\TextColumn::make('pdf_generated_at')->label('PDF')->dateTime()->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ')->dateTime(),
            ])
            ->paginated(false);
    }
}
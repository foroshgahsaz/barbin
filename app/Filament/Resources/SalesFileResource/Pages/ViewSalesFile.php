<?php

namespace App\Filament\Resources\SalesFileResource\Pages;

use App\Filament\Resources\SalesFileResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesFile extends ViewRecord
{
    protected static string $resource = SalesFileResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('title')->label('عنوان'),
            TextEntry::make('product_count')->label('تعداد محصول'),
            TextEntry::make('processing_status')->label('وضعیت'),
            RepeatableEntry::make('items')->label('ردیف‌ها')->schema([
                TextEntry::make('row_number')->label('ردیف'),
                TextEntry::make('product_code')->label('کد کالا'),
                TextEntry::make('description')->label('شرح'),
                TextEntry::make('stock_quantity')->label('موجودی'),
                TextEntry::make('stock_status')->label('وضعیت تطبیق'),
            ]),
        ]);
    }
}
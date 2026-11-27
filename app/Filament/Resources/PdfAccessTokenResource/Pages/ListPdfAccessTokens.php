<?php

namespace App\Filament\Resources\PdfAccessTokenResource\Pages;

use App\Filament\Resources\PdfAccessTokenResource;
use Filament\Resources\Pages\ListRecords;

class ListPdfAccessTokens extends ListRecords
{
    protected static string $resource = PdfAccessTokenResource::class;
}
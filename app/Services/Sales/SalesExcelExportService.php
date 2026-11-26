<?php

namespace App\Services\Sales;

use App\Enums\StockMatchStatus;
use App\Models\SalesFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SalesExcelExportService
{
    public function export(SalesFile $salesFile): string
    {
        $salesFile->loadMissing('items');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);

        $headers = ['ردیف', 'کد کالا', 'شرح کالا', 'واحد کالا', 'مبلغ', 'موجودی', 'وضعیت تطبیق'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($salesFile->items as $item) {
            $stock = match ($item->stock_status) {
                StockMatchStatus::NotFound => 'نامشخص',
                default => (string) ($item->stock_quantity ?? '0'),
            };

            $sheet->fromArray([
                $item->row_number,
                $item->product_code,
                $item->description,
                $item->unit,
                $item->price,
                $stock,
                $item->stock_status?->value ?? '',
            ], null, 'A'.$row);
            $row++;
        }

        $relative = 'sales-exports/'.$salesFile->id.'/'.uniqid('export_', true).'.xlsx';
        $absolute = Storage::disk('local')->path($relative);
        @mkdir(dirname($absolute), 0775, true);

        (new Xlsx($spreadsheet))->save($absolute);

        $salesFile->update(['output_excel_path' => $relative]);

        return $relative;
    }
}
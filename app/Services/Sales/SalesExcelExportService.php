<?php

namespace App\Services\Sales;

use App\Models\SalesFile;
use App\Support\StockDisplay;
use App\Support\StoragePaths;
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
            $sheet->fromArray([
                $item->row_number,
                $item->product_code,
                $item->description,
                $item->unit,
                $item->price,
                StockDisplay::quantityForDisplay(
                    $item->stock_quantity !== null ? (float) $item->stock_quantity : null,
                    $item->stock_status,
                ),
                $item->stock_status?->label() ?? '—',
            ], null, 'A'.$row);
            $row++;
        }

        $relative = 'sales-exports/'.$salesFile->id.'/'.uniqid('export_', true).'.xlsx';
        $temp = tempnam(sys_get_temp_dir(), 'barbin_xlsx_');
        (new Xlsx($spreadsheet))->save($temp);
        StoragePaths::putGeneratedFile($relative, (string) file_get_contents($temp));
        @unlink($temp);

        $salesFile->update(['output_excel_path' => $relative]);

        return $relative;
    }
}
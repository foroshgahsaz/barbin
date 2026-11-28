<?php

namespace App\Services\Pdf;

use App\Enums\StockMatchStatus;
use App\Models\ProductImageCode;
use App\Models\SalesFile;
use App\Models\SystemSetting;
use App\Support\MpdfTempDir;
use App\Support\StoragePaths;
use App\Support\StockDisplay;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class SalesPdfGenerator
{
    public function generate(SalesFile $salesFile): string
    {
        $salesFile->loadMissing(['items', 'owner']);

        $branding = $this->branding();

        $imageMap = $this->resolveImages($salesFile);

        $html = view('pdf.sales-list', [
            'salesFile' => $salesFile,
            'items' => $salesFile->items,
            'branding' => $branding,
            'imageMap' => $imageMap,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'tempDir' => MpdfTempDir::path(),
        ]);

        $mpdf->SetHTMLFooter($this->footerHtml($branding));
        $mpdf->WriteHTML($html);

        $relativePath = 'sales-pdfs/'.$salesFile->id.'/'.uniqid('list_', true).'.pdf';
        StoragePaths::ensureDirectory('sales-pdfs/'.$salesFile->id);

        $pdfBinary = $mpdf->Output('', Destination::STRING_RETURN);
        StoragePaths::putGeneratedFile($relativePath, $pdfBinary);

        $salesFile->update([
            'pdf_path' => $relativePath,
            'pdf_generated_at' => now(),
        ]);

        return $relativePath;
    }

    /**
     * @return array<string, string>
     */
    private function resolveImages(SalesFile $salesFile): array
    {
        $codes = $salesFile->items->pluck('product_code')->unique()->values();
        $links = ProductImageCode::query()
            ->whereIn('product_code', $codes)
            ->with('image')
            ->get();

        $map = [];
        foreach ($links as $link) {
            if (! $link->image || ! $link->image->is_active) {
                continue;
            }
            if (! isset($map[$link->product_code])) {
                $rel = $link->image->thumbnail_path ?: $link->image->file_path;
                $map[$link->product_code] = Storage::disk('local')->path($rel);
            }
        }

        return $map;
    }

    private function branding(): array
    {
        return [
            'company_name' => SystemSetting::getValue('branding', 'company_name', config('app.name')),
            'phone' => SystemSetting::getValue('branding', 'phone', ''),
            'website' => SystemSetting::getValue('branding', 'website', ''),
            'address' => SystemSetting::getValue('branding', 'address', ''),
            'logo_path' => SystemSetting::getValue('branding', 'logo_path'),
        ];
    }

    private function footerHtml(array $branding): string
    {
        $name = e($branding['company_name']);
        $phone = e($branding['phone']);
        $site = e($branding['website']);

        return "<div style='text-align:center;font-size:9px;'>{$name} | {$phone} | {$site}</div>";
    }

    public function formatStockCell(\App\Models\SalesFileItem $item): string
    {
        return StockDisplay::quantityForDisplay(
            $item->stock_quantity !== null ? (float) $item->stock_quantity : null,
            $item->stock_status,
        );
    }
}
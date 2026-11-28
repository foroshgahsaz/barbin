<?php

namespace App\Services\Pdf;

use App\Models\CatalogProduct;
use App\Models\ProductImageCode;
use App\Models\SalesFile;
use App\Models\SystemSetting;
use App\Support\MediaPath;
use App\Support\MpdfTempDir;
use App\Support\SalesOutputFilter;
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
        $items = SalesOutputFilter::filterItems($salesFile->items);
        $imageMap = $this->resolveImages($items);

        $html = view('pdf.sales-list', [
            'salesFile' => $salesFile,
            'items' => $items,
            'branding' => $branding,
            'imageMap' => $imageMap,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'tempDir' => MpdfTempDir::path(),
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 18,
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
     * @param  list<\App\Models\SalesFileItem>  $items
     * @return array<string, string>
     */
    private function resolveImages(array $items): array
    {
        $codes = collect($items)->pluck('product_code')->unique()->values()->all();
        if ($codes === []) {
            return [];
        }

        $map = [];

        $catalog = CatalogProduct::query()
            ->whereIn('product_code', $codes)
            ->whereNotNull('product_image_id')
            ->with('image')
            ->get();

        foreach ($catalog as $product) {
            if ($product->image && $product->image->is_active) {
                $rel = $product->image->thumbnail_path ?: $product->image->file_path;
                $abs = MediaPath::absolute($rel);
                if ($abs !== null) {
                    $map[$product->product_code] = $abs;
                }
            }
        }

        $missing = array_diff($codes, array_keys($map));
        if ($missing !== []) {
            $links = ProductImageCode::query()
                ->whereIn('product_code', $missing)
                ->with('image')
                ->get();

            foreach ($links as $link) {
                if (! $link->image || ! $link->image->is_active) {
                    continue;
                }
                if (isset($map[$link->product_code])) {
                    continue;
                }
                $rel = $link->image->thumbnail_path ?: $link->image->file_path;
                $abs = MediaPath::absolute($rel);
                if ($abs !== null) {
                    $map[$link->product_code] = $abs;
                }
            }
        }

        return $map;
    }

    private function branding(): array
    {
        $logoPath = SystemSetting::getValue('branding', 'logo_path');
        $logoAbsolute = null;
        if (filled($logoPath)) {
            $path = is_array($logoPath) ? ($logoPath[0] ?? null) : $logoPath;
            if (filled($path)) {
                $logoAbsolute = MediaPath::absolute((string) $path);
            }
        }

        return [
            'company_name' => SystemSetting::getValue('branding', 'company_name', config('app.name')),
            'phone' => SystemSetting::getValue('branding', 'phone', ''),
            'website' => SystemSetting::getValue('branding', 'website', ''),
            'address' => SystemSetting::getValue('branding', 'address', ''),
            'logo_path' => $logoPath,
            'logo_absolute' => $logoAbsolute,
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
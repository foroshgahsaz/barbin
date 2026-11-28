<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 0; }
        .cover { text-align: center; padding: 48px 24px 24px; }
        .cover-logo { max-width: 200px; max-height: 120px; margin: 0 auto 24px; }
        .cover-title { font-size: 20px; font-weight: bold; margin-bottom: 8px; }
        .cover-sub { font-size: 12px; color: #555; margin-bottom: 24px; }
        .cover-line { font-size: 13px; margin: 6px 0; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 10px 14px; }
        .grid td { width: 50%; vertical-align: top; }
        .card { border: 1px solid #d1d5db; border-radius: 12px; overflow: hidden; background: #fff; }
        .card-img-wrap { height: 155px; text-align: center; background: #f9fafb; padding: 8px; }
        .card-img { max-height: 140px; max-width: 100%; object-fit: contain; }
        .card-body { padding: 10px 12px 12px; text-align: center; }
        .card-name { font-size: 11px; font-weight: bold; line-height: 1.5; min-height: 34px; }
        .card-meta { margin-top: 8px; font-size: 11px; }
        .card-meta table { width: 100%; border: none; }
        .card-meta td { border: none; padding: 2px 4px; }
        .price { text-align: left; direction: ltr; }
        .stock { text-align: right; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="cover">
        @php($logoAbs = $branding['logo_absolute'] ?? null)
        @if($logoAbs && file_exists($logoAbs))
            <img class="cover-logo" src="{{ $logoAbs }}" alt="">
        @endif
        <div class="cover-title">{{ $branding['company_name'] }}</div>
        <div class="cover-sub">لیست فروش: {{ $salesFile->title }}</div>
        <div class="cover-line">تاریخ: {{ \App\Support\JalaliDate::format(now()) }}</div>
        @if(filled($branding['phone']))
            <div class="cover-line">تلفن: {{ $branding['phone'] }}</div>
        @endif
        @if(filled($branding['address']))
            <div class="cover-line">آدرس: {{ $branding['address'] }}</div>
        @endif
        @if(filled($branding['website']))
            <div class="cover-line">وب‌سایت: {{ $branding['website'] }}</div>
        @endif
        @if($salesFile->owner)
            <div class="cover-line">مدیر فروش: {{ $salesFile->owner->name }}</div>
        @endif
    </div>

    <div class="page-break"></div>

    <table class="grid">
        @foreach(collect($items)->chunk(2) as $pair)
            <tr>
                @foreach($pair as $item)
                    <td>
                        <div class="card">
                            <div class="card-img-wrap">
                                @php($path = $imageMap[\App\Support\ProductCode::normalize($item->product_code) ?? $item->product_code] ?? null)
                                @if($path && file_exists($path))
                                    <img class="card-img" src="{{ $path }}" alt="">
                                @else
                                    <span style="line-height:140px;color:#9ca3af;font-size:10px;">بدون تصویر</span>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="card-name">{{ $item->description ?: $item->product_code }}</div>
                                <div class="card-meta">
                                    <table>
                                        <tr>
                                            <td class="stock">موجودی: {{ \App\Support\StockDisplay::quantityForDisplay(
                                                $item->stock_quantity !== null ? (float) $item->stock_quantity : null,
                                                $item->stock_status,
                                            ) }}</td>
                                            <td class="price">قیمت: {{ $item->price !== null ? number_format((float) $item->price, 0) : '—' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </td>
                @endforeach
                @if($pair->count() === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
</body>
</html>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px 6px; vertical-align: middle; }
        th { background: #f3f4f6; }
        .img { width: 52px; height: 52px; object-fit: contain; }
        .title { font-size: 14px; margin-bottom: 8px; }
        .meta { margin-bottom: 12px; color: #555; }
    </style>
</head>
<body>
    <div class="title">لیست فروش: {{ $salesFile->title }}</div>
    <div class="meta">
        تاریخ تولید: {{ \App\Support\JalaliDate::format(now()) }}
        @if($salesFile->owner)
            | مدیر فروش: {{ $salesFile->owner->name }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>ردیف</th>
                <th>کد کالا</th>
                <th>شرح کالا</th>
                <th>واحد</th>
                <th>مبلغ</th>
                <th>موجودی</th>
                <th>تصویر</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->row_number }}</td>
                    <td>{{ $item->product_code }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->unit }}</td>
                    <td>{{ $item->price !== null ? number_format((float) $item->price, 0) : '-' }}</td>
                    <td>{{ \App\Support\StockDisplay::quantityForDisplay(
                        $item->stock_quantity !== null ? (float) $item->stock_quantity : null,
                        $item->stock_status,
                    ) }}</td>
                    <td style="text-align:center;">
                        @php($path = $imageMap[$item->product_code] ?? null)
                        @if($path && file_exists($path))
                            <img class="img" src="{{ $path }}" alt="">
                        @else
                            <span>تصویر موجود نیست</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
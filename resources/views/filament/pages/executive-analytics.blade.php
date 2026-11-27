<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3 mb-6">
        <x-filament::section>
            <x-slot name="heading">سرمایه خوابیده (ریال)</x-slot>
            {{ number_format($capital['total_value'] ?? 0) }}
            <p class="text-xs text-gray-500 mt-1">{{ $capital['priced_lines'] ?? 0 }} کالا با قیمت خرید</p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">لیست فروش (۹۰ روز)</x-slot>
            {{ $salesSummary['files_count'] ?? 0 }}
            <p class="text-xs text-gray-500 mt-1">PDF: {{ $salesSummary['pdf_count'] ?? 0 }}</p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">نرخ تطبیق موجودی</x-slot>
            {{ $matchBreakdown['match_rate'] ?? 0 }}%
        </x-filament::section>
    </div>

    <x-filament::section class="mb-6">
        <x-slot name="heading">تغییر Snapshot آخر به قبل</x-slot>
        <p class="text-sm">
            {{ $stockDelta['first_date'] ?? '—' }} → {{ $stockDelta['last_date'] ?? '—' }}:
            افزوده {{ $stockDelta['added'] ?? 0 }}، حذف {{ $stockDelta['removed'] ?? 0 }}، تغییر مقدار {{ $stockDelta['changed'] ?? 0 }}
        </p>
    </x-filament::section>

    <x-filament::section class="mb-6">
        <x-slot name="heading">ارسال پیام (۹۰ روز)</x-slot>
        <p class="text-sm">موفق: {{ $delivery['sent'] ?? 0 }} — ناموفق: {{ $delivery['failed'] ?? 0 }} — در انتظار: {{ $delivery['pending'] ?? 0 }}</p>
    </x-filament::section>

    <div class="grid md:grid-cols-2 gap-4 mb-6">
        <x-filament::section>
            <x-slot name="heading">پرتکرار در لیست‌های فروش</x-slot>
            <ul class="text-sm list-disc ps-5">
                @foreach($topProducts as $row)
                    <li>{{ $row['product_code'] }} ({{ $row['appearances'] }})</li>
                @endforeach
            </ul>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">فایل فروش ماهانه</x-slot>
            <ul class="text-sm list-disc ps-5">
                @foreach($filesPerMonth as $row)
                    <li>{{ $row['month'] }}: {{ $row['count'] }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">ABC ارزشی (قیمت خرید × موجودی)</x-slot>
        <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div><strong>A</strong><ul class="list-disc ps-4">@foreach(array_slice($abcValue['a'] ?? [], 0, 6) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
            <div><strong>B</strong><ul class="list-disc ps-4">@foreach(array_slice($abcValue['b'] ?? [], 0, 6) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
            <div><strong>C</strong><ul class="list-disc ps-4">@foreach(array_slice($abcValue['c'] ?? [], 0, 6) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3 mb-6">
        <x-filament::section>
            <x-slot name="heading">کل کالاها</x-slot>
            {{ $summary['items_count'] ?? 0 }}
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">دارای موجودی</x-slot>
            {{ $summary['in_stock_count'] ?? 0 }}
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">بدون موجودی</x-slot>
            {{ $summary['zero_stock_count'] ?? 0 }}
        </x-filament::section>
    </div>

    <x-filament::section class="mb-6">
        <x-slot name="heading">روند Snapshot (۹۰ روز)</x-slot>
        @if(empty($timeline))
            <p class="text-sm text-gray-500">داده‌ای نیست — سیدر دمو را اجرا کنید: <code>php artisan barbin:seed-demo</code></p>
        @else
            <table class="w-full text-sm">
                <thead><tr><th class="text-start p-1">تاریخ</th><th class="text-start p-1">کل</th><th class="text-start p-1">موجود</th></tr></thead>
                <tbody>
                @foreach($timeline as $row)
                    <tr class="border-t"><td class="p-1">{{ $row['date'] }}</td><td class="p-1">{{ $row['items'] }}</td><td class="p-1">{{ $row['in_stock'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <x-filament::section class="mb-6">
        <x-slot name="heading">{{ $unchanged['label'] ?? 'عدم تغییر موجودی' }}</x-slot>
        @if(empty($unchanged['items']))
            <p class="text-sm text-gray-500">حداقل دو Snapshot لازم است.</p>
        @else
            <ul class="text-sm list-disc ps-5 max-h-48 overflow-y-auto">
                @foreach(array_slice($unchanged['items'], 0, 20) as $item)
                    <li>{{ $item->product_code }} — {{ $item->description }}</li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

    @if(auth()->user()?->can('reports.warehouse_ops'))
        <div class="grid md:grid-cols-2 gap-4">
            <x-filament::section>
                <x-slot name="heading">موجودی بحرانی (۱ تا ۵)</x-slot>
                <ul class="text-sm list-disc ps-5 max-h-48 overflow-y-auto">
                    @forelse(array_slice($critical, 0, 15) as $item)
                        <li>{{ $item->product_code }} ({{ $item->quantity }})</li>
                    @empty
                        <li class="list-none text-gray-500">—</li>
                    @endforelse
                </ul>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">موجودی مازاد (≥ ۵۰)</x-slot>
                <ul class="text-sm list-disc ps-5 max-h-48 overflow-y-auto">
                    @forelse(array_slice($excess, 0, 15) as $item)
                        <li>{{ $item->product_code }} ({{ $item->quantity }})</li>
                    @empty
                        <li class="list-none text-gray-500">—</li>
                    @endforelse
                </ul>
            </x-filament::section>
        </div>
    @endif

    <x-filament::section class="mt-6">
        <x-slot name="heading">ABC بر اساس سهم مقدار موجودی (نه ارزش ریالی)</x-slot>
        <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div><strong>A</strong><ul class="list-disc ps-4">@foreach(array_slice($abc['a'] ?? [], 0, 8) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
            <div><strong>B</strong><ul class="list-disc ps-4">@foreach(array_slice($abc['b'] ?? [], 0, 8) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
            <div><strong>C</strong><ul class="list-disc ps-4">@foreach(array_slice($abc['c'] ?? [], 0, 8) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
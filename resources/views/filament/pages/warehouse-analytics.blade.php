<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-4 mb-6">
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
        <x-filament::section>
            <x-slot name="heading">سرمایه خوابیده</x-slot>
            {{ number_format($capital['total_value'] ?? 0) }} <span class="text-xs">ریال</span>
        </x-filament::section>
    </div>

    <x-filament::section class="mb-6">
        <x-slot name="heading">روند Snapshot (۹۰ روز)</x-slot>
        @if(empty($timeline))
            <p class="text-sm text-gray-500">داده‌ای نیست — <code>php artisan barbin:seed-demo</code></p>
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
        <x-slot name="heading">تفاوت دو Snapshot آخر</x-slot>
        <p class="text-sm">{{ $stockDelta['first_date'] ?? '—' }} → {{ $stockDelta['last_date'] ?? '—' }} — افزوده {{ $stockDelta['added'] ?? 0 }}، حذف {{ $stockDelta['removed'] ?? 0 }}، تغییر {{ $stockDelta['changed'] ?? 0 }}</p>
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
        <div class="grid md:grid-cols-2 gap-4 mb-6">
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
                <x-slot name="heading">پیشنهاد خرید (کم‌موجود + دارای قیمت خرید)</x-slot>
                <ul class="text-sm list-disc ps-5 max-h-48 overflow-y-auto">
                    @forelse($reorder as $item)
                        <li>{{ $item['product_code'] }} — {{ $item['quantity'] }}</li>
                    @empty
                        <li class="list-none text-gray-500">—</li>
                    @endforelse
                </ul>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">موجودی مازاد (≥ ۵۰ عدد)</x-slot>
                <ul class="text-sm list-disc ps-5 max-h-48 overflow-y-auto">
                    @forelse(array_slice($excess, 0, 15) as $item)
                        <li>{{ $item->product_code }} ({{ $item->quantity }})</li>
                    @empty
                        <li class="list-none text-gray-500">—</li>
                    @endforelse
                </ul>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">مازاد ارزشی</x-slot>
                <ul class="text-sm list-disc ps-5 max-h-48 overflow-y-auto">
                    @forelse($excessValue as $item)
                        <li>{{ $item->product_code }}</li>
                    @empty
                        <li class="list-none text-gray-500">قیمت خرید در Snapshot وارد کنید</li>
                    @endforelse
                </ul>
            </x-filament::section>
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-4 mb-6">
        <x-filament::section>
            <x-slot name="heading">کالاهای بدون موجودی</x-slot>
            <ul class="text-sm list-disc ps-5 max-h-40 overflow-y-auto">
                @foreach(array_slice($zeroStock, 0, 12) as $item)
                    <li>{{ $item->product_code }}</li>
                @endforeach
            </ul>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">کندگردش (موجود اما در لیست فروش نیامده)</x-slot>
            <ul class="text-sm list-disc ps-5 max-h-40 overflow-y-auto">
                @foreach($slowMovers as $item)
                    <li>{{ $item['product_code'] }} ({{ $item['quantity'] }})</li>
                @endforeach
            </ul>
        </x-filament::section>
    </div>

    <x-filament::section class="mb-6">
        <x-slot name="heading">ABC بر اساس مقدار موجودی</x-slot>
        <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div><strong>A</strong><ul class="list-disc ps-4">@foreach(array_slice($abc['a'] ?? [], 0, 8) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
            <div><strong>B</strong><ul class="list-disc ps-4">@foreach(array_slice($abc['b'] ?? [], 0, 8) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
            <div><strong>C</strong><ul class="list-disc ps-4">@foreach(array_slice($abc['c'] ?? [], 0, 8) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
        </div>
    </x-filament::section>

    <x-filament::section class="mb-6">
        <x-slot name="heading">ABC ارزشی (قیمت خرید)</x-slot>
        <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div><strong>A</strong><ul class="list-disc ps-4">@foreach(array_slice($abcValue['a'] ?? [], 0, 8) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
            <div><strong>B</strong><ul class="list-disc ps-4">@foreach(array_slice($abcValue['b'] ?? [], 0, 8) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
            <div><strong>C</strong><ul class="list-disc ps-4">@foreach(array_slice($abcValue['c'] ?? [], 0, 8) as $i)<li>{{ $i->product_code }}</li>@endforeach</ul></div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">XYZ (نوسان موجودی — X پرنوسان، Z پایدار)</x-slot>
        <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div><strong>X</strong><ul class="list-disc ps-4">@foreach($xyz['x'] ?? [] as $i)<li>{{ $i['product_code'] }}</li>@endforeach</ul></div>
            <div><strong>Y</strong><ul class="list-disc ps-4">@foreach($xyz['y'] ?? [] as $i)<li>{{ $i['product_code'] }}</li>@endforeach</ul></div>
            <div><strong>Z</strong><ul class="list-disc ps-4">@foreach($xyz['z'] ?? [] as $i)<li>{{ $i['product_code'] }}</li>@endforeach</ul></div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
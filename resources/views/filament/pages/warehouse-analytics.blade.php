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

    <x-filament::section>
        <x-slot name="heading">{{ $unchanged['label'] ?? 'تحلیل' }}</x-slot>
        @if(empty($unchanged['items']))
            <p class="text-sm text-gray-500">داده کافی برای این بازه نیست (حداقل دو Snapshot).</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-start">
                    <thead>
                        <tr>
                            <th class="p-2">کد کالا</th>
                            <th class="p-2">شرح</th>
                            <th class="p-2">موجودی</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($unchanged['items'], 0, 50) as $item)
                            <tr class="border-t">
                                <td class="p-2">{{ $item->product_code }}</td>
                                <td class="p-2">{{ $item->description }}</td>
                                <td class="p-2">{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    @if(auth()->user()?->can('reports.warehouse_ops'))
        <x-filament::section class="mt-6">
            <x-slot name="heading">موجودی بحرانی (۱ تا ۵)</x-slot>
            @if(empty($critical))
                <p class="text-sm text-gray-500">Snapshot موجود نیست یا کالایی در این بازه نیست.</p>
            @else
                <ul class="text-sm list-disc ps-5">
                    @foreach(array_slice($critical, 0, 30) as $item)
                        <li>{{ $item->product_code }} — {{ $item->description }} ({{ $item->quantity }})</li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
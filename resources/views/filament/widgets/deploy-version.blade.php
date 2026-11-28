<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">نسخهٔ کد روی سرور</x-slot>
        @php($report = $this->deployReport())
        @if($this->allFeaturesOk())
            <p class="text-sm text-success-600 dark:text-success-400 font-medium mb-3">
                کد جدید روی این پاد فعال است ({{ $report['expected_merge'] }}).
            </p>
        @else
            <p class="text-sm text-danger-600 dark:text-danger-400 font-medium mb-3">
                پاد هنوز <strong>قدیمی</strong> یا ناقص است — Runflare: شاخه <code>main</code>، Redeploy / Rebuild <strong>بدون کش image</strong>، سپس دستورات پایین.
            </p>
        @endif
        <p class="text-xs text-gray-600 dark:text-gray-400 mb-3 direction-ltr text-start">
            بررسی سریع در مرورگر: <a class="underline" href="{{ $report['urls']['peyda_font'] }}" target="_blank">/fonts/PeydaWebVF.woff2</a>
            — باید دانلود شود، نه ۴۰۴.
        </p>
        <ul class="text-sm space-y-1 mb-4">
            @foreach($this->featureChecks() as $item)
                <li class="flex items-center gap-2">
                    <span class="{{ $item['ok'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $item['ok'] ? '✓' : '✗' }}</span>
                    {{ $item['label'] }}
                </li>
            @endforeach
        </ul>
        <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded-lg overflow-x-auto direction-ltr text-start">php artisan barbin:verify-deploy
php artisan barbin:ensure-storage
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache</pre>
    </x-filament::section>
</x-filament-widgets::widget>
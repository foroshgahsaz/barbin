<x-filament-panels::page>
    <div class="flex flex-wrap gap-3 items-end mb-4">
        <div class="min-w-[12rem]">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">فایل لاگ</label>
            <select wire:model.live="logFile" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                @forelse($this->logFiles as $file)
                    <option value="{{ $file }}">{{ $file }}</option>
                @empty
                    <option value="laravel.log">laravel.log</option>
                @endforelse
            </select>
        </div>
        <div class="min-w-[10rem]">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">نمایش</label>
            <select wire:model.live="viewMode" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                <option value="errors">فقط خطاها</option>
                <option value="tail">آخر فایل (خام)</option>
            </select>
        </div>
        <x-filament::button wire:click="refreshLogs" icon="heroicon-o-arrow-path" size="sm" color="gray">
            بروزرسانی
        </x-filament::button>
        <p class="text-xs text-gray-500 dark:text-gray-400 pb-1">
            حجم: {{ $payload['size_human'] ?? '—' }} —
            خطاهای یافت‌شده در بخش انتهایی: <strong>{{ $payload['error_count'] ?? 0 }}</strong>
        </p>
    </div>

    @if(!($payload['exists'] ?? false))
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">فایل لاگ وجود ندارد یا قابل خواندن نیست.</p>
        </x-filament::section>
    @elseif($viewMode === 'errors')
        @if(empty($payload['errors']))
            <x-filament::section>
                <p class="text-sm text-emerald-700 dark:text-emerald-400">در بخش خوانده‌شده از فایل، خطای ERROR ثبت نشده است.</p>
            </x-filament::section>
        @else
            <div class="space-y-3">
                @foreach($payload['errors'] as $error)
                    <div class="rounded-xl border border-danger-500/40 bg-danger-500/5 p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <x-filament::badge color="danger">{{ $error['level'] }}</x-filament::badge>
                        </div>
                        <pre class="text-xs whitespace-pre-wrap break-words font-mono text-gray-800 dark:text-gray-100 direction-ltr text-start">{{ $error['headline'] }}</pre>
                        @if(!empty($error['body']))
                            <pre class="mt-2 text-xs whitespace-pre-wrap break-words font-mono text-gray-600 dark:text-gray-300 max-h-48 overflow-y-auto direction-ltr text-start">{{ $error['body'] }}</pre>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <x-filament::section heading="آخرین خطوط (حداکثر ~۵۱۲KB انتهای فایل)">
            <pre class="text-xs whitespace-pre-wrap break-words font-mono text-gray-700 dark:text-gray-200 max-h-[32rem] overflow-y-auto direction-ltr text-start">@foreach($payload['tail_lines'] ?? [] as $line){{ $line }}
@endforeach</pre>
        </x-filament::section>
    @endif
</x-filament-panels::page>
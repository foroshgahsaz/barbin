<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4 flex gap-2">
            <x-filament::button type="submit">ذخیره</x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="testSms">تست اتصال</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
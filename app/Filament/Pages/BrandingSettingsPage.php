<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BrandingSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static string $view = 'filament.pages.branding-settings';

    protected static ?string $navigationLabel = 'برندینگ و PDF';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?string $title = 'برندینگ و PDF';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'company_name' => SystemSetting::getValue('branding', 'company_name', config('app.name')),
            'phone' => SystemSetting::getValue('branding', 'phone', ''),
            'website' => SystemSetting::getValue('branding', 'website', ''),
            'address' => SystemSetting::getValue('branding', 'address', ''),
            'logo_path' => SystemSetting::getValue('branding', 'logo_path'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('company_name')->label('نام مجموعه')->required(),
            TextInput::make('phone')->label('تلفن'),
            TextInput::make('website')->label('وب‌سایت'),
            TextInput::make('address')->label('آدرس'),
            FileUpload::make('logo_path')->label('لوگو')->image()->disk('local')->directory('branding'),
        ])->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        foreach (['company_name', 'phone', 'website', 'address', 'logo_path'] as $key) {
            $val = is_array($state[$key] ?? null) ? ($state[$key][0] ?? null) : ($state[$key] ?? null);
            SystemSetting::putValue('branding', $key, $val);
        }
        Notification::make()->title('ذخیره شد')->success()->send();
    }
}
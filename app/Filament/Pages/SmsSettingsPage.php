<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use App\Services\Sms\SmsIrService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SmsSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string $view = 'filament.pages.sms-settings';

    protected static ?string $navigationLabel = 'تنظیمات SMS.ir';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?string $title = 'تنظیمات SMS.ir';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('sms.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'api_key' => SystemSetting::getValue('sms_ir', 'api_key', ''),
            'template_id' => SystemSetting::getValue('sms_ir', 'template_id', ''),
            'template_parameters' => SystemSetting::getValue('sms_ir', 'template_parameters', '[]'),
            'is_active' => (bool) SystemSetting::getValue('sms_ir', 'is_active', false),
            'test_mobile' => SystemSetting::getValue('sms_ir', 'test_mobile', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('is_active')->label('فعال'),
                TextInput::make('api_key')->label('API Key')->password()->revealable(),
                TextInput::make('template_id')->label('Template ID'),
                Textarea::make('template_parameters')->label('پارامترهای الگو (JSON)')->rows(4),
                TextInput::make('test_mobile')->label('موبایل تست'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        SystemSetting::putValue('sms_ir', 'is_active', $state['is_active'] ? '1' : '0');
        SystemSetting::putValue('sms_ir', 'api_key', $state['api_key'] ?? '', true);
        SystemSetting::putValue('sms_ir', 'template_id', $state['template_id'] ?? '');
        SystemSetting::putValue('sms_ir', 'template_parameters', $state['template_parameters'] ?? '[]');
        SystemSetting::putValue('sms_ir', 'test_mobile', $state['test_mobile'] ?? '');

        Notification::make()->title('ذخیره شد')->success()->send();
    }

    public function testSms(): void
    {
        $mobile = $this->data['test_mobile'] ?? '';
        $result = app(SmsIrService::class)->testConnection($mobile);
        Notification::make()
            ->title($result['success'] ?? false ? 'تست موفق' : 'تست ناموفق')
            ->body($result['message'] ?? json_encode($result, JSON_UNESCAPED_UNICODE))
            ->{$result['success'] ?? false ? 'success' : 'danger'}()
            ->send();
    }
}
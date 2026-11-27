<?php

namespace App\Providers\Filament;

use App\Filament\Pages\BrandingSettingsPage;
use App\Filament\Pages\SmsSettingsPage;
use App\Filament\Pages\ExecutiveAnalyticsPage;
use App\Filament\Pages\WarehouseAnalyticsPage;
use App\Filament\Resources\PdfAccessTokenResource;
use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\CustomerGroupResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\DeliveryLogResource;
use App\Filament\Resources\InventoryImportBatchResource;
use App\Filament\Resources\InventorySnapshotResource;
use App\Filament\Resources\MessengerResource;
use App\Filament\Resources\ProductImageResource;
use App\Filament\Resources\SalesFileResource;
use App\Filament\Resources\UserResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Barbin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                InventoryImportBatchResource::class,
                InventorySnapshotResource::class,
                SalesFileResource::class,
                CustomerResource::class,
                CustomerGroupResource::class,
                ProductImageResource::class,
                DeliveryLogResource::class,
                UserResource::class,
                MessengerResource::class,
                AuditLogResource::class,
                PdfAccessTokenResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                SmsSettingsPage::class,
                BrandingSettingsPage::class,
                WarehouseAnalyticsPage::class,
                ExecutiveAnalyticsPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\BarbinStatsWidget::class,
                \App\Filament\Widgets\RecentSalesFilesWidget::class,
                \App\Filament\Widgets\ExecutiveSummaryWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
<?php

namespace App\Filament\Widgets;

use App\Support\DeployProbe;
use Filament\Widgets\Widget;

class DeployVersionWidget extends Widget
{
    protected static string $view = 'filament.widgets.deploy-version';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    /**
     * @return array<int, array{label: string, ok: bool}>
     */
    public function featureChecks(): array
    {
        return array_map(
            fn (array $row): array => ['label' => $row['label'], 'ok' => $row['ok']],
            DeployProbe::checks(),
        );
    }

    public function allFeaturesOk(): bool
    {
        return DeployProbe::allOk();
    }

    public function deployReport(): array
    {
        return DeployProbe::report();
    }
}
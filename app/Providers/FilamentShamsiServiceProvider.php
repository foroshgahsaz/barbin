<?php

namespace App\Providers;

use App\Support\JalaliDate;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;

class FilamentShamsiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        TextColumn::macro('shamsiDateTime', function (?string $pattern = null, bool $withTime = true) {
            /** @var TextColumn $this */
            $pattern ??= $withTime ? 'Y/m/d H:i' : 'Y/m/d';

            return $this->formatStateUsing(
                fn ($state) => JalaliDate::format($state, $pattern),
            );
        });

        TextColumn::macro('shamsiDate', function (?string $pattern = null) {
            /** @var TextColumn $this */
            $pattern ??= 'Y/m/d';

            return $this->shamsiDateTime($pattern, withTime: false);
        });

        TextEntry::macro('shamsiDateTime', function (?string $pattern = null, bool $withTime = true) {
            /** @var TextEntry $this */
            $pattern ??= $withTime ? 'Y/m/d H:i' : 'Y/m/d';

            return $this->formatStateUsing(
                fn ($state) => JalaliDate::format($state, $pattern),
            );
        });

        TextEntry::macro('shamsiDate', function (?string $pattern = null) {
            /** @var TextEntry $this */
            $pattern ??= 'Y/m/d';

            return $this->shamsiDateTime($pattern, withTime: false);
        });
    }
}
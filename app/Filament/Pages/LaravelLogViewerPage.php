<?php

namespace App\Filament\Pages;

use App\Services\Logs\LaravelLogReader;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class LaravelLogViewerPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string $view = 'filament.pages.laravel-log-viewer';

    protected static ?string $navigationLabel = 'لاگ خطاها';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?string $title = 'مشاهده لاگ Laravel';

    protected static ?int $navigationSort = 99;

    public string $logFile = 'laravel.log';

    public string $viewMode = 'errors';

    public array $payload = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(LaravelLogReader $reader): void
    {
        $files = $reader->listLogFiles();
        if ($files !== [] && ! in_array($this->logFile, $files, true)) {
            $this->logFile = $files[0];
        }
        $this->reload($reader);
    }

    public function updatedLogFile(): void
    {
        $this->reload(app(LaravelLogReader::class));
    }

    public function updatedViewMode(): void
    {
        $this->reload(app(LaravelLogReader::class));
    }

    public function refreshLogs(): void
    {
        $this->reload(app(LaravelLogReader::class));
    }

    private function reload(LaravelLogReader $reader): void
    {
        try {
            $this->payload = $reader->read($this->logFile);
        } catch (\Throwable $e) {
            $this->payload = [
                'exists' => false,
                'file' => $this->logFile,
                'size_human' => '—',
                'errors' => [],
                'tail_lines' => [$e->getMessage()],
                'error_count' => 0,
            ];
        }
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function logFiles(): array
    {
        return app(LaravelLogReader::class)->listLogFiles();
    }
}
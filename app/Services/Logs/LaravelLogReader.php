<?php

namespace App\Services\Logs;

use Illuminate\Support\Str;

final class LaravelLogReader
{
    private const ERROR_LEVELS = [
        'EMERGENCY',
        'ALERT',
        'CRITICAL',
        'ERROR',
    ];

    /**
     * @return list<string>
     */
    public function listLogFiles(): array
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir.'/laravel*.log') ?: [];

        usort($files, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        return array_map(fn (string $path) => basename($path), $files);
    }

    /**
     * @return array{
     *   exists: bool,
     *   file: string,
     *   size_human: string,
     *   errors: list<array{level: string, headline: string, body: string}>,
     *   tail_lines: list<string>,
     *   error_count: int
     * }
     */
    public function read(string $basename, int $tailBytes = 524288, int $maxEntries = 80): array
    {
        $basename = basename($basename);
        if (! Str::startsWith($basename, 'laravel') || ! Str::endsWith($basename, '.log')) {
            throw new \InvalidArgumentException('فقط فایل‌های laravel*.log مجاز است.');
        }

        $path = storage_path('logs/'.$basename);
        if (! is_file($path) || ! is_readable($path)) {
            return [
                'exists' => false,
                'file' => $basename,
                'size_human' => '—',
                'errors' => [],
                'tail_lines' => [],
                'error_count' => 0,
            ];
        }

        $content = $this->tailContent($path, $tailBytes);
        $entries = $this->splitEntries($content);
        $errors = [];
        foreach ($entries as $entry) {
            $parsed = $this->parseEntry($entry);
            if ($parsed === null) {
                continue;
            }
            if (in_array($parsed['level'], self::ERROR_LEVELS, true)) {
                $errors[] = $parsed;
            }
        }

        $errors = array_slice($errors, 0, $maxEntries);

        return [
            'exists' => true,
            'file' => $basename,
            'size_human' => $this->humanSize((int) filesize($path)),
            'errors' => $errors,
            'tail_lines' => array_slice(explode("\n", $content), -120),
            'error_count' => count($errors),
        ];
    }

    private function tailContent(string $path, int $maxBytes): string
    {
        $size = (int) filesize($path);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return '';
        }

        if ($size > $maxBytes) {
            fseek($handle, -$maxBytes, SEEK_END);
            fgets($handle);
        }

        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    /**
     * @return list<string>
     */
    private function splitEntries(string $content): array
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return [];
        }

        $parts = preg_split('/(?=^\[\d{4}-\d{2}-\d{2})/m', $trimmed) ?: [];

        return array_values(array_filter($parts, fn ($p) => trim($p) !== ''));
    }

    /**
     * @return array{level: string, headline: string, body: string}|null
     */
    private function parseEntry(string $entry): ?array
    {
        $lines = explode("\n", trim($entry));
        $headline = $lines[0] ?? '';

        if (! preg_match('/\.(EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):\s*(.*)$/s', $headline, $m)) {
            return [
                'level' => 'INFO',
                'headline' => $headline,
                'body' => implode("\n", array_slice($lines, 1)),
            ];
        }

        return [
            'level' => $m[1],
            'headline' => $headline,
            'body' => implode("\n", array_slice($lines, 1)),
        ];
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }
}
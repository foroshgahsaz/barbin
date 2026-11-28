<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class StorageDiagnostics
{
    /**
     * @return array<string, mixed>
     */
    public static function run(): array
    {
        $phpUser = self::phpProcessUser();
        $shellUser = self::shellUser();

        $disks = [
            'barbin' => (string) config('filesystems.disks.barbin.root'),
            'local' => (string) config('filesystems.disks.local.root'),
        ];

        $paths = [];
        foreach ($disks as $name => $root) {
            $paths[$name] = self::inspectPath($root, $name);
        }

        $probeResults = [];
        foreach (['barbin', 'local'] as $diskName) {
            $probeResults[$diskName] = self::writeProbe($diskName, 'sales-exports/.barbin-write-probe');
        }

        return [
            'shell_user' => $shellUser,
            'php_user' => $phpUser,
            'barbin_files_disk' => BarbinStorage::diskName(),
            'disks' => $paths,
            'write_probes' => $probeResults,
            'fix_as_root' => self::fixCommands($disks, $phpUser),
        ];
    }

    public static function formatForConsole(array $report): string
    {
        $lines = [];
        $lines[] = '=== Barbin Storage Diagnostics ===';
        $lines[] = 'Shell (شما در ترمینال): '.$report['shell_user'];
        $lines[] = 'PHP (وب/Livewire): '.$report['php_user'];
        $lines[] = 'BARBIN_FILES_DISK: '.$report['barbin_files_disk'];
        $lines[] = '';

        foreach ($report['disks'] as $name => $info) {
            $lines[] = "--- دیسک {$name} ---";
            $lines[] = '  root: '.$info['path'];
            $lines[] = '  exists: '.($info['exists'] ? 'yes' : 'no');
            $lines[] = '  is_dir: '.($info['is_dir'] ? 'yes' : 'no');
            $lines[] = '  is_readable: '.($info['is_readable'] ? 'yes' : 'no');
            $lines[] = '  is_writable: '.($info['is_writable'] ? 'yes' : 'no');
            if ($info['owner'] !== null) {
                $lines[] = '  owner: '.$info['owner'];
            }
            if ($info['perms'] !== null) {
                $lines[] = '  perms: '.$info['perms'];
            }
            if ($info['mount'] ?? false) {
                $lines[] = '  (روی mount جدا — معمولاً نیاز به chown برای کاربر PHP)';
            }
            $lines[] = '';
        }

        foreach ($report['write_probes'] as $disk => $probe) {
            $lines[] = "Probe write [{$disk}]: ".($probe['ok'] ? 'OK' : 'FAIL — '.$probe['error']);
        }

        $lines[] = '';
        $lines[] = '=== اگر FAIL بود و root هستید، این‌ها را بزنید ===';
        foreach ($report['fix_as_root'] as $cmd) {
            $lines[] = $cmd;
        }
        $lines[] = '';
        $lines[] = 'سپس: php artisan barbin:ensure-storage';

        return implode("\n", $lines);
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    public static function writeProbe(string $diskName, string $relativePath): array
    {
        try {
            StoragePaths::putGeneratedFile($relativePath, 'probe-'.now()->timestamp);

            if (Storage::disk($diskName)->exists($relativePath)) {
                Storage::disk($diskName)->delete($relativePath);
            }

            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, string>  $disks
     * @return list<string>
     */
    private static function fixCommands(array $disks, string $phpUser): array
    {
        $user = $phpUser !== 'unknown' ? $phpUser : 'www-data';
        $cmds = [];

        foreach (array_unique(array_values($disks)) as $root) {
            if ($root === '') {
                continue;
            }
            $cmds[] = "mkdir -p {$root}";
            $cmds[] = "chown -R {$user}:{$user} {$root}";
            $cmds[] = "chmod -R ug+rwx {$root}";
        }

        $cmds[] = 'mkdir -p '.storage_path('logs').' '.storage_path('app/logs');
        $cmds[] = "chown -R {$user}:{$user} ".storage_path('logs').' '.storage_path('framework');
        $cmds[] = 'php artisan barbin:ensure-storage';

        return $cmds;
    }

    private static function phpProcessUser(): string
    {
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $info = posix_getpwuid(posix_geteuid());

            return $info['name'] ?? 'uid:'.posix_geteuid();
        }

        $user = @get_current_user();

        return $user !== '' ? $user : 'unknown';
    }

    private static function shellUser(): string
    {
        $whoami = trim((string) shell_exec('whoami 2>/dev/null'));

        return $whoami !== '' ? $whoami : self::phpProcessUser();
    }

    /**
     * @return array<string, mixed>
     */
    private static function inspectPath(string $path, string $diskName): array
    {
        $owner = null;
        $perms = null;

        if (is_file($path) || is_dir($path)) {
            $perms = substr(sprintf('%o', @fileperms($path)), -4);
            if (function_exists('posix_getpwuid')) {
                $stat = @stat($path);
                if ($stat !== false) {
                    $pw = posix_getpwuid($stat['uid']);
                    $owner = $pw['name'] ?? (string) $stat['uid'];
                }
            }
        }

        $isMount = is_dir($path) && is_readable('/proc/mounts')
            && str_contains((string) @file_get_contents('/proc/mounts'), $path);

        return [
            'disk' => $diskName,
            'path' => $path,
            'exists' => file_exists($path),
            'is_dir' => is_dir($path),
            'is_readable' => is_readable($path),
            'is_writable' => is_writable($path),
            'owner' => $owner,
            'perms' => $perms,
            'mount' => $isMount,
        ];
    }
}
<?php

namespace App\Services\Logs;

use App\Models\ApplicationErrorLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

final class AdminErrorLogger
{
    private static bool $recording = false;

    public static function record(
        string $source,
        string $message,
        string $level = 'error',
        array $context = [],
    ): void {
        if (self::$recording) {
            return;
        }

        self::$recording = true;

        try {
            $message = Str::limit($message, 65000);

            ApplicationErrorLog::query()->create([
                'user_id' => Auth::id(),
                'level' => $level,
                'source' => Str::limit($source, 120),
                'message' => $message,
                'context' => $context === [] ? null : $context,
            ]);
        } catch (Throwable) {
            // دیتابیس تنها منبع اطمینان روی Runflare؛ از Log:: استفاده نمی‌کنیم تا حلقه نشود.
        } finally {
            self::$recording = false;
        }
    }

    public static function fromThrowable(Throwable $e, ?string $source = null): void
    {
        if (self::isLoggingInfrastructureFailure($e)) {
            return;
        }

        self::record(
            $source ?? class_basename($e),
            $e->getMessage(),
            'error',
            [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
        );
    }

    public static function isLoggingInfrastructureFailure(Throwable $e): bool
    {
        if ($e instanceof \UnexpectedValueException
            && str_contains($e->getMessage(), 'could not be opened')) {
            return true;
        }

        return str_contains($e->getFile(), 'Monolog'.DIRECTORY_SEPARATOR.'Handler');
    }
}
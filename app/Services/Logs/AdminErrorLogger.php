<?php

namespace App\Services\Logs;

use App\Models\ApplicationErrorLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class AdminErrorLogger
{
    public static function record(
        string $source,
        string $message,
        string $level = 'error',
        array $context = [],
    ): void {
        $message = Str::limit($message, 65000);
        $userId = Auth::id();

        try {
            ApplicationErrorLog::query()->create([
                'user_id' => $userId,
                'level' => $level,
                'source' => Str::limit($source, 120),
                'message' => $message,
                'context' => $context === [] ? null : $context,
            ]);
        } catch (Throwable $e) {
            Log::warning('AdminErrorLogger: could not persist to database', [
                'source' => $source,
                'db_error' => $e->getMessage(),
            ]);
        }

        Log::log($level, "[{$source}] {$message}", $context);
    }

    public static function fromThrowable(Throwable $e, ?string $source = null): void
    {
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
}
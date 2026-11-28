<?php

namespace Tests\Unit;

use App\Models\ApplicationErrorLog;
use App\Models\User;
use App\Services\Logs\AdminErrorLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use UnexpectedValueException;

class AdminErrorLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_messenger_test_failure(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        AdminErrorLogger::record('messenger.test', 'کانال پیدا نشد', context: [
            'messenger_id' => 1,
        ]);

        $this->assertDatabaseCount('application_error_logs', 1);
        $log = ApplicationErrorLog::query()->first();
        $this->assertSame('messenger.test', $log->source);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_skips_logging_infrastructure_exceptions(): void
    {
        $e = new UnexpectedValueException('The stream or file "/var/www/storage/logs/laravel.log" could not be opened');

        $this->assertTrue(AdminErrorLogger::isLoggingInfrastructureFailure($e));
    }
}
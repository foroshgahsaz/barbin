<?php

namespace Tests\Unit;

use App\Models\Messenger;
use App\Services\Messengers\MessengerManager;
use App\Services\Messengers\TelegramDriver;
use Tests\TestCase;

class MessengerManagerTest extends TestCase
{
    public function test_resolves_telegram_driver(): void
    {
        $messenger = new Messenger(['driver' => 'telegram']);
        $driver = (new MessengerManager)->driver($messenger);

        $this->assertInstanceOf(TelegramDriver::class, $driver);
    }
}
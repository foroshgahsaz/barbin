<?php

namespace App\Services\Messengers;

use App\Models\Messenger;
use InvalidArgumentException;

class MessengerManager
{
    public function driver(Messenger $messenger): MessengerDriverInterface
    {
        return match ($messenger->driver) {
            'bale' => new BaleDriver($messenger),
            'telegram' => new TelegramDriver($messenger),
            'rubika' => new RubikaDriver($messenger),
            default => throw new InvalidArgumentException("Driver {$messenger->driver} پشتیبانی نمی‌شود."),
        };
    }
}
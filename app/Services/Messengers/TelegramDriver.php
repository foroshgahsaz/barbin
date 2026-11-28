<?php

namespace App\Services\Messengers;

class TelegramDriver extends BotApiChannelDriver
{
    protected function botApiRoot(): string
    {
        $base = config('barbin.telegram.api_base', 'https://api.telegram.org');

        return rtrim($base, '/').'/bot';
    }
}
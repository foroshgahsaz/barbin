<?php

namespace App\Services\Messengers;

class BaleDriver extends BotApiChannelDriver
{
    protected function botApiRoot(): string
    {
        return 'https://tapi.bale.ai/bot';
    }
}
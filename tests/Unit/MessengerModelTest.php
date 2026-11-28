<?php

namespace Tests\Unit;

use App\Models\Messenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessengerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_token_is_encrypted_at_rest(): void
    {
        $plain = '123456:AAExampleTelegramBotToken';

        $messenger = Messenger::query()->create([
            'driver' => 'telegram',
            'name' => 'Test bot',
            'bot_token' => $plain,
            'channel_id' => '-100123',
            'is_active' => true,
        ]);

        $this->assertSame($plain, $messenger->bot_token);
        $this->assertNotSame($plain, $messenger->getRawOriginal('bot_token'));

        $reloaded = Messenger::query()->findOrFail($messenger->id);
        $this->assertSame($plain, $reloaded->bot_token);
    }
}
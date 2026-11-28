<?php

namespace App\Services\Messengers;

use App\Models\Messenger;
use Illuminate\Support\Facades\Http;

/**
 * API سازگار با Bot API (تلگرام، بله، …)
 */
abstract class BotApiChannelDriver implements MessengerDriverInterface
{
    public function __construct(protected readonly Messenger $messenger) {}

    abstract protected function botApiRoot(): string;

    public function testConnection(): array
    {
        if (! $this->messenger->channel_id) {
            return ['success' => false, 'error' => 'شناسه کانال (Channel ID) تنظیم نشده است.'];
        }

        return $this->sendToChannel((string) $this->messenger->channel_id, 'تست اتصال Barbin ✅');
    }

    public function sendToChannel(string $channelId, string $message, ?string $documentPath = null): array
    {
        $token = $this->messenger->bot_token;
        if ($token === null || $token === '') {
            return ['success' => false, 'error' => 'Bot Token خالی است.'];
        }

        $base = rtrim($this->botApiRoot(), '/').$token;

        try {
            if ($documentPath && is_file($documentPath)) {
                $response = Http::timeout(120)
                    ->attach('document', file_get_contents($documentPath), basename($documentPath))
                    ->post("{$base}/sendDocument", [
                        'chat_id' => $channelId,
                        'caption' => $message,
                    ]);
            } else {
                $response = Http::timeout(30)->post("{$base}/sendMessage", [
                    'chat_id' => $channelId,
                    'text' => $message,
                ]);
            }

            $json = $response->json();
            $ok = $response->successful() && ($json['ok'] ?? false);

            return [
                'success' => $ok,
                'message_id' => isset($json['result']['message_id']) ? (string) $json['result']['message_id'] : null,
                'raw' => $json,
                'error' => $ok ? null : ($json['description'] ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
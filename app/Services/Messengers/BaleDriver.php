<?php

namespace App\Services\Messengers;

use App\Models\Messenger;
use Illuminate\Support\Facades\Http;

class BaleDriver implements MessengerDriverInterface
{
    public function __construct(private readonly Messenger $messenger) {}

    public function testConnection(): array
    {
        if (! $this->messenger->channel_id) {
            return ['success' => false, 'error' => 'Channel ID تنظیم نشده است.'];
        }

        return $this->sendToChannel($this->messenger->channel_id, 'تست اتصال Barbin');
    }

    public function sendToChannel(string $channelId, string $message, ?string $documentPath = null): array
    {
        $token = $this->messenger->bot_token;
        $base = 'https://tapi.bale.ai/bot'.$token;

        try {
            if ($documentPath && is_file($documentPath)) {
                $response = Http::attach('document', file_get_contents($documentPath), basename($documentPath))
                    ->post("{$base}/sendDocument", [
                        'chat_id' => $channelId,
                        'caption' => $message,
                    ]);
            } else {
                $response = Http::post("{$base}/sendMessage", [
                    'chat_id' => $channelId,
                    'text' => $message,
                ]);
            }

            $json = $response->json();

            return [
                'success' => $response->successful() && ($json['ok'] ?? false),
                'message_id' => isset($json['result']['message_id']) ? (string) $json['result']['message_id'] : null,
                'raw' => $json,
                'error' => $response->successful() ? null : $response->body(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
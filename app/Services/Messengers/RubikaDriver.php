<?php

namespace App\Services\Messengers;

use App\Models\Messenger;
use Illuminate\Support\Facades\Http;

class RubikaDriver implements MessengerDriverInterface
{
    public function __construct(private readonly Messenger $messenger) {}

    public function testConnection(): array
    {
        return [
            'success' => false,
            'error' => 'اتصال روبیکا نیازمند URL و قرارداد API رسمی پروژه شماست؛ توکن و channel را ذخیره کرده‌ایم.',
        ];
    }

    public function sendToChannel(string $channelId, string $message, ?string $documentPath = null): array
    {
        $endpoint = config('services.rubika.send_endpoint');

        if (! $endpoint) {
            return ['success' => false, 'error' => 'ROUBIKA_SEND_ENDPOINT در env تنظیم نشده است.'];
        }

        try {
            $response = Http::withToken($this->messenger->bot_token)
                ->post($endpoint, [
                    'channel_id' => $channelId,
                    'text' => $message,
                    'file' => $documentPath,
                ]);

            return [
                'success' => $response->successful(),
                'raw' => $response->json(),
                'error' => $response->successful() ? null : $response->body(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
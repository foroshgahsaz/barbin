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

        $channelId = $this->normalizeChannelId((string) $this->messenger->channel_id);

        return $this->sendToChannel($channelId, 'تست اتصال Barbin ✅');
    }

    public function sendToChannel(string $channelId, string $message, ?string $documentPath = null): array
    {
        $channelId = $this->normalizeChannelId($channelId);

        $token = $this->messenger->bot_token;
        if ($token === null || $token === '') {
            return ['success' => false, 'error' => 'Bot Token خالی است.'];
        }

        $base = rtrim($this->botApiRoot(), '/').$token;

        try {
            if ($documentPath && is_file($documentPath)) {
                $stream = fopen($documentPath, 'rb');
                if ($stream === false) {
                    return ['success' => false, 'error' => 'خواندن فایل پیوست ممکن نشد: '.$documentPath];
                }

                try {
                    $response = Http::timeout(120)
                        ->attach(
                            'document',
                            $stream,
                            basename($documentPath),
                            ['Content-Type' => 'application/pdf'],
                        )
                        ->post("{$base}/sendDocument", [
                            'chat_id' => $channelId,
                            'caption' => $message,
                        ]);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            } else {
                $response = Http::timeout(30)->post("{$base}/sendMessage", [
                    'chat_id' => $channelId,
                    'text' => $message,
                ]);
            }

            $json = $response->json();
            $ok = $response->successful() && ($json['ok'] ?? false);

            $apiError = $ok ? null : ($json['description'] ?? $response->body());

            return [
                'success' => $ok,
                'message_id' => isset($json['result']['message_id']) ? (string) $json['result']['message_id'] : null,
                'raw' => $json,
                'error' => $ok ? null : $this->humanizeApiError($apiError),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function normalizeChannelId(string $channelId): string
    {
        $channelId = trim($channelId);

        if (preg_match('#t\.me/([A-Za-z0-9_]+)#', $channelId, $m)) {
            return '@'.$m[1];
        }

        if (str_starts_with($channelId, 'https://') || str_starts_with($channelId, 'http://')) {
            return $channelId;
        }

        if ($channelId !== '' && ! str_starts_with($channelId, '@') && ! preg_match('/^-?\d+$/', $channelId)) {
            return '@'.ltrim($channelId, '@');
        }

        return $channelId;
    }

    protected function humanizeApiError(?string $error): ?string
    {
        if ($error === null || $error === '') {
            return null;
        }

        if (stripos($error, 'chat not found') !== false) {
            return 'کانال پیدا نشد. بات را ادمین کانال کنید؛ شناسه را @username (کانال عمومی) یا -100… (خصوصی) وارد کنید — نه لینک t.me و نه chat_id شخصی.';
        }

        if (stripos($error, 'bot was blocked') !== false) {
            return 'بات بلاک شده یا به کانال اضافه نشده است.';
        }

        if (stripos($error, 'not enough rights') !== false || stripos($error, 'CHAT_WRITE_FORBIDDEN') !== false) {
            return 'بات حق ارسال پیام در کانال را ندارد — در تنظیمات ادمین کانال، Post Messages را فعال کنید.';
        }

        if (stripos($error, "can't send messages to the bot") !== false) {
            return 'شناسه کانال اشتباه است (به نظر chat_id خود بات است). شناسه کانال را بگذارید: @username کانال یا -100… — نه توکن و نه آیدی بات.';
        }

        return $error;
    }
}
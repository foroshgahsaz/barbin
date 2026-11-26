<?php

namespace App\Services\Messengers;

interface MessengerDriverInterface
{
    public function testConnection(): array;

    /**
     * @return array{success: bool, message_id?: string, error?: string, raw?: mixed}
     */
    public function sendToChannel(string $channelId, string $message, ?string $documentPath = null): array;
}
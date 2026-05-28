<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

final class RealtimeBroadcastClient
{
    public function publish(string $type, array $payload): void
    {
        $url = trim((string) ($_ENV['WS_BROADCAST_URL'] ?? ''));
        if ($url === '') {
            return;
        }

        $secret = trim((string) ($_ENV['WS_BROADCAST_SECRET'] ?? ''));

        try {
            HttpClient::create()->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-WS-Secret' => $secret,
                ],
                'json' => [
                    'type' => $type,
                    'payload' => $payload,
                ],
                'timeout' => 3,
            ]);
        } catch (\Throwable) {
            // Realtime broadcasts must never break order/request creation flow.
        }
    }
}

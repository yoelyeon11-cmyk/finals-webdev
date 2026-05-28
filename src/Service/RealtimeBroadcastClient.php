<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

final class RealtimeBroadcastClient
{
    public function publish(string $type, array $payload): void
    {
        $url = $this->env('WS_BROADCAST_URL');
        if ($url === '') {
            return;
        }

        $secret = $this->env('WS_BROADCAST_SECRET');

        try {
            $response = HttpClient::create()->request('POST', $url, [
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
            // Force the HTTP request to complete before PHP exits this request.
            $response->getStatusCode();
        } catch (\Throwable) {
            // Realtime broadcasts must never break order/request creation flow.
        }
    }

    private function env(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? '';
        return trim((string) $value, " \t\n\r\0\x0B\"'");
    }
}

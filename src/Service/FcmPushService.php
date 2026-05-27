<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends push notifications via Firebase Cloud Messaging (HTTP v1 or legacy API).
 *
 * Railway env (pick one):
 * - FIREBASE_SERVICE_ACCOUNT_JSON: full service account JSON (recommended)
 * - FCM_LEGACY_SERVER_KEY: legacy server key from Firebase Cloud Messaging settings
 */
final class FcmPushService
{
    private ?HttpClientInterface $httpClient = null;

    private readonly string $serviceAccountJson;

    private readonly string $legacyServerKey;

    private readonly string $projectId;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        // Read via getenv so JSON with "%" (e.g. %40 in URLs) is not mangled by Symfony parameter expansion.
        $this->serviceAccountJson = self::readEnv('FIREBASE_SERVICE_ACCOUNT_JSON');
        $this->legacyServerKey = self::readEnv('FCM_LEGACY_SERVER_KEY');
        $this->projectId = self::readEnv('FIREBASE_PROJECT_ID') ?: 'cloudrobe-bd8af';
    }

    private static function readEnv(string $key): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return is_string($value) ? $value : '';
    }

    public function isConfigured(): bool
    {
        return $this->legacyServerKey !== '' || $this->parseServiceAccount() !== null;
    }

    /**
     * @param array<string, string> $data
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $deviceToken = trim($deviceToken);
        if ($deviceToken === '') {
            return false;
        }

        if ($this->legacyServerKey !== '') {
            return $this->sendLegacy($deviceToken, $title, $body, $data);
        }

        $account = $this->parseServiceAccount();
        if ($account !== null) {
            return $this->sendHttpV1($account, $deviceToken, $title, $body, $data);
        }

        $this->logger->warning('FCM not configured: set FIREBASE_SERVICE_ACCOUNT_JSON or FCM_LEGACY_SERVER_KEY on the server.');

        return false;
    }

    /** @param array<string, string> $data */
    private function sendLegacy(string $deviceToken, string $title, string $body, array $data): bool
    {
        try {
            $response = $this->client()->request('POST', 'https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Authorization' => 'key=' . $this->legacyServerKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'to' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                ],
                'timeout' => 15,
            ]);

            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return true;
            }

            $this->logger->warning('FCM legacy send failed', [
                'status' => $status,
                'body' => $response->getContent(false),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('FCM legacy send error', ['exception' => $e]);
        }

        return false;
    }

    /**
     * @param array{project_id: string, client_email: string, private_key: string} $account
     * @param array<string, string> $data
     */
    private function sendHttpV1(array $account, string $deviceToken, string $title, string $body, array $data): bool
    {
        $accessToken = $this->fetchAccessToken($account);
        if ($accessToken === null) {
            return false;
        }

        $projectId = $account['project_id'] ?: $this->projectId;

        try {
            $response = $this->client()->request(
                'POST',
                sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $projectId),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'message' => [
                            'token' => $deviceToken,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => $data,
                            'android' => [
                                'priority' => 'HIGH',
                                'notification' => [
                                    'channel_id' => 'order_updates',
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                    'timeout' => 15,
                ],
            );

            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return true;
            }

            $this->logger->warning('FCM v1 send failed', [
                'status' => $status,
                'body' => $response->getContent(false),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('FCM v1 send error', ['exception' => $e]);
        }

        return false;
    }

    /**
     * @param array{project_id: string, client_email: string, private_key: string} $account
     */
    private function fetchAccessToken(array $account): ?string
    {
        try {
            $now = time();
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claim = $this->base64UrlEncode(json_encode([
                'iss' => $account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));
            $unsigned = $header . '.' . $claim;

            $privateKey = openssl_pkey_get_private($account['private_key']);
            if ($privateKey === false) {
                return null;
            }

            $signature = '';
            if (!openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                return null;
            }

            $jwt = $unsigned . '.' . $this->base64UrlEncode($signature);

            $response = $this->client()->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ],
                'timeout' => 15,
            ]);

            $payload = $response->toArray(false);

            return isset($payload['access_token']) ? (string) $payload['access_token'] : null;
        } catch (\Throwable $e) {
            $this->logger->error('FCM OAuth token error', ['exception' => $e]);

            return null;
        }
    }

    /** @return array{project_id: string, client_email: string, private_key: string}|null */
    private function parseServiceAccount(): ?array
    {
        if ($this->serviceAccountJson === '') {
            return null;
        }

        try {
            $data = json_decode($this->serviceAccountJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (
            !is_array($data)
            || empty($data['client_email'])
            || empty($data['private_key'])
        ) {
            return null;
        }

        return [
            'project_id' => (string) ($data['project_id'] ?? $this->projectId),
            'client_email' => (string) $data['client_email'],
            'private_key' => (string) $data['private_key'],
        ];
    }

    private function client(): HttpClientInterface
    {
        return $this->httpClient ??= HttpClient::create();
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

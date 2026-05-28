<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

final class OrderRealtimeEventStore
{
    private const EMPTY_STORE = ['events' => []];

    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public function publishStatusChanged(Order $order, string $oldStatus): void
    {
        $email = trim((string) $order->getCustomerEmail());
        if ($email === '') {
            return;
        }

        $store = $this->readStore();
        $cursor = (int) floor(microtime(true) * 1000);

        $store['events'][$email] = [
            'cursor' => $cursor,
            'event' => 'order.status.updated',
            'transactionId' => $order->getTransactionId(),
            'oldStatus' => $oldStatus,
            'status' => $order->getStatus(),
            'statusLabel' => $order->getStatusLabel(),
            'updatedAt' => $order->getUpdatedAt()?->format(DATE_ATOM),
        ];

        $this->writeStore($store);
    }

    /** @return array<string, mixed>|null */
    public function waitForEvent(string $customerEmail, int $sinceCursor, int $timeoutSeconds = 20): ?array
    {
        $deadline = microtime(true) + max(1, $timeoutSeconds);
        $email = trim($customerEmail);

        while (microtime(true) < $deadline) {
            $store = $this->readStore();
            $event = $store['events'][$email] ?? null;
            if (is_array($event) && (int) ($event['cursor'] ?? 0) > $sinceCursor) {
                return $event;
            }

            usleep(500000);
        }

        return null;
    }

    /** @return array{events: array<string, array<string, mixed>>} */
    private function readStore(): array
    {
        $path = $this->storePath();
        if (!is_file($path)) {
            return self::EMPTY_STORE;
        }

        $json = file_get_contents($path);
        if (!is_string($json) || $json === '') {
            return self::EMPTY_STORE;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !isset($decoded['events']) || !is_array($decoded['events'])) {
            return self::EMPTY_STORE;
        }

        return $decoded;
    }

    /** @param array{events: array<string, array<string, mixed>>} $store */
    private function writeStore(array $store): void
    {
        $path = $this->storePath();
        (new Filesystem())->mkdir(\dirname($path));
        file_put_contents($path, json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function storePath(): string
    {
        return $this->kernel->getProjectDir() . '/var/order-realtime-events.json';
    }
}

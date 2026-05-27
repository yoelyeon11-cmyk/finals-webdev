<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;

final class OrderStatusPushNotifier
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly FcmPushService $fcm,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyIfStatusChanged(Order $order, string $previousStatus): void
    {
        $newStatus = (string) $order->getStatus();
        if ($newStatus === '' || $newStatus === $previousStatus) {
            return;
        }

        $email = trim((string) $order->getCustomerEmail());
        if ($email === '') {
            return;
        }

        /** @var User|null $user */
        $user = $this->users->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $this->logger->info('Order status push skipped: no user for email', ['email' => $email]);

            return;
        }

        $token = $user->getFcmToken();
        if ($token === null || trim($token) === '') {
            $this->logger->info('Order status push skipped: user has no FCM token', ['email' => $email]);

            return;
        }

        $title = 'Cloudrobe order update';
        $body = sprintf(
            'Order %s is now: %s',
            $order->getTransactionId(),
            $order->getStatusLabel(),
        );

        if ($newStatus === 'shipping' && $order->getTrackingNumber()) {
            $body .= sprintf(' (Tracking: %s)', $order->getTrackingNumber());
        }

        $this->fcm->sendToDevice($token, $title, $body, [
            'type' => 'order_status',
            'transactionId' => (string) $order->getTransactionId(),
            'status' => $newStatus,
            'statusLabel' => $order->getStatusLabel(),
        ]);
    }
}

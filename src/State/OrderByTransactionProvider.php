<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Order;
use App\Repository\OrderRepository;

/**
 * @implements ProviderInterface<Order|null>
 */
final class OrderByTransactionProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderRepository $orders,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Order
    {
        $transactionId = $uriVariables['transactionId'] ?? null;
        if (!is_string($transactionId) || $transactionId === '') {
            return null;
        }

        return $this->orders->findOneBy(['transactionId' => $transactionId]);
    }
}

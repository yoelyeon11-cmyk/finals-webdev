<?php

namespace App\Controller\Api\V1;

use App\Entity\CustomCosplayRequest;
use App\Entity\Order;
use App\Entity\Products;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\CustomCosplayRequestRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductsRepository;
use App\Service\OrderRealtimeEventStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
final class CustomerController extends AbstractController
{
    #[Route('/categories', name: 'api_v1_categories_index', methods: ['GET'])]
    public function categories(CategoryRepository $categories): JsonResponse
    {
        $items = $categories->findAll();

        return $this->json([
            'success' => true,
            'data' => array_map(static fn ($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
            ], $items),
            'error' => null,
        ]);
    }

    #[Route('/orders', name: 'api_v1_orders_index', methods: ['GET'])]
    public function orders(OrderRepository $orders): JsonResponse
    {
        $user = $this->requireUser();

        $list = $orders->findBy(
            ['customerEmail' => $user->getEmail()],
            ['orderDate' => 'DESC'],
        );

        return $this->json([
            'success' => true,
            'data' => array_map(fn (Order $o) => $this->serializeOrder($o), $list),
            'error' => null,
        ]);
    }

    #[Route('/orders', name: 'api_v1_orders_create', methods: ['POST'])]
    public function createOrder(
        Request $request,
        EntityManagerInterface $em,
        ProductsRepository $productsRepository,
    ): JsonResponse {
        $user = $this->requireUser();
        $payload = $this->decodeJson($request);

        $items = $payload['items'] ?? [];
        if (!is_array($items) || $items === []) {
            return $this->error('validation_error', 'At least one cart item is required.', 422);
        }

        $paymentMethod = trim((string) ($payload['paymentMethod'] ?? 'cash'));
        $shippingAddress = trim((string) ($payload['shippingAddress'] ?? ''));
        $customerPhone = trim((string) ($payload['customerPhone'] ?? ''));

        if ($shippingAddress === '') {
            return $this->error('validation_error', 'shippingAddress is required.', 422);
        }

        $lines = [];
        $total = 0.0;

        foreach ($items as $item) {
            $productId = (int) ($item['productId'] ?? 0);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            /** @var Products|null $product */
            $product = $productId > 0 ? $productsRepository->find($productId) : null;
            $name = $product?->getName() ?? trim((string) ($item['name'] ?? 'Item'));
            $price = (float) ($product?->getPrice() ?? $item['price'] ?? 0);
            $lineTotal = $price * $quantity;
            $total += $lineTotal;
            $lines[] = sprintf('%s x%d (₱%.2f)', $name, $quantity, $lineTotal);
        }

        $order = new Order();
        $order->setCustomerName($user->getFullName() ?? $user->getUsername() ?? 'Customer');
        $order->setCustomerEmail($user->getEmail());
        $order->setCustomerPhone($customerPhone !== '' ? $customerPhone : null);
        $order->setItemsDescription(implode('; ', $lines));
        $order->setTotalAmount(number_format($total, 2, '.', ''));
        $order->setPaymentMethod($paymentMethod);
        $order->setShippingAddress($shippingAddress);
        $order->setStatus('new_order');
        $order->setCreatedBy($user);

        $em->persist($order);
        $em->flush();

        return $this->json([
            'success' => true,
            'data' => $this->serializeOrder($order),
            'error' => null,
        ], 201);
    }

    #[Route('/orders/{transactionId}', name: 'api_v1_orders_show', methods: ['GET'])]
    public function orderShow(string $transactionId, OrderRepository $orders): JsonResponse
    {
        $user = $this->requireUser();
        $order = $orders->findOneBy(['transactionId' => $transactionId]);

        if (!$order || $order->getCustomerEmail() !== $user->getEmail()) {
            return $this->error('not_found', 'Order not found.', 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->serializeOrder($order),
            'error' => null,
        ]);
    }

    #[Route('/orders/realtime/events', name: 'api_v1_orders_realtime', methods: ['GET'])]
    public function orderRealtime(Request $request, OrderRealtimeEventStore $eventStore): JsonResponse
    {
        $user = $this->requireUser();
        $since = max(0, (int) $request->query->get('since', 0));
        $event = $eventStore->waitForEvent($user->getEmail(), $since, 20);

        return $this->json([
            'success' => true,
            'data' => $event,
            'error' => null,
        ]);
    }

    #[Route('/custom-requests', name: 'api_v1_custom_requests_index', methods: ['GET'])]
    public function customRequests(CustomCosplayRequestRepository $repository): JsonResponse
    {
        $user = $this->requireUser();

        $list = $repository->findBy(
            ['customerEmail' => $user->getEmail()],
            ['createdAt' => 'DESC'],
        );

        return $this->json([
            'success' => true,
            'data' => array_map(fn (CustomCosplayRequest $r) => $this->serializeCustomRequest($r), $list),
            'error' => null,
        ]);
    }

    #[Route('/custom-requests', name: 'api_v1_custom_requests_create', methods: ['POST'])]
    public function createCustomRequest(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser();
        $payload = $this->decodeJson($request);

        $character = trim((string) ($payload['cosplayCharacter'] ?? ''));
        if ($character === '') {
            return $this->error('validation_error', 'cosplayCharacter is required.', 422);
        }

        $requestEntity = new CustomCosplayRequest();
        $requestEntity->setCustomerName($user->getFullName() ?? $user->getUsername() ?? 'Customer');
        $requestEntity->setCustomerEmail($user->getEmail());
        $requestEntity->setCustomerPhone($this->nullableString($payload['customerPhone'] ?? null));
        $requestEntity->setCosplayCharacter($character);
        $requestEntity->setDesignNotes($this->nullableString($payload['designNotes'] ?? null));
        $requestEntity->setBust($this->nullableDecimal($payload['bust'] ?? null));
        $requestEntity->setWaist($this->nullableDecimal($payload['waist'] ?? null));
        $requestEntity->setHip($this->nullableDecimal($payload['hip'] ?? null));
        $requestEntity->setShoulderWidth($this->nullableDecimal($payload['shoulderWidth'] ?? null));
        $requestEntity->setInseam($this->nullableDecimal($payload['inseam'] ?? null));
        $requestEntity->setHeight($this->nullableDecimal($payload['height'] ?? null));
        $requestEntity->setCustomMeasurements($this->nullableString($payload['customMeasurements'] ?? null));
        $requestEntity->setStatus('new_request');
        $requestEntity->setCreatedBy($user);

        $em->persist($requestEntity);
        $em->flush();

        return $this->json([
            'success' => true,
            'data' => $this->serializeCustomRequest($requestEntity),
            'error' => null,
        ], 201);
    }

    #[Route('/custom-requests/{id}', name: 'api_v1_custom_requests_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function customRequestShow(int $id, CustomCosplayRequestRepository $repository): JsonResponse
    {
        $user = $this->requireUser();
        $requestEntity = $repository->find($id);

        if (!$requestEntity || $requestEntity->getCustomerEmail() !== $user->getEmail()) {
            return $this->error('not_found', 'Custom request not found.', 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->serializeCustomRequest($requestEntity),
            'error' => null,
        ]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        return $user;
    }

    /** @return array<string, mixed> */
    private function decodeJson(Request $request): array
    {
        $payload = json_decode((string) $request->getContent(), true);

        return is_array($payload) ? $payload : [];
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return $this->json([
            'success' => false,
            'data' => null,
            'error' => ['code' => $code, 'message' => $message],
        ], $status);
    }

    /** @return array<string, mixed> */
    private function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'transactionId' => $order->getTransactionId(),
            'customerName' => $order->getCustomerName(),
            'customerEmail' => $order->getCustomerEmail(),
            'itemsDescription' => $order->getItemsDescription(),
            'totalAmount' => $order->getTotalAmount(),
            'paymentMethod' => $order->getPaymentMethod(),
            'shippingAddress' => $order->getShippingAddress(),
            'status' => $order->getStatus(),
            'statusLabel' => $order->getStatusLabel(),
            'shippingCarrier' => $order->getShippingCarrier(),
            'trackingNumber' => $order->getTrackingNumber(),
            'orderDate' => $order->getOrderDate()?->format(DATE_ATOM),
            'updatedAt' => $order->getUpdatedAt()?->format(DATE_ATOM),
            'completedAt' => $order->getCompletedAt()?->format(DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeCustomRequest(CustomCosplayRequest $request): array
    {
        $progressSteps = $this->customRequestProgressSteps($request->getStatus() ?? 'new_request');

        return [
            'id' => $request->getId(),
            'customerName' => $request->getCustomerName(),
            'customerEmail' => $request->getCustomerEmail(),
            'customerPhone' => $request->getCustomerPhone(),
            'cosplayCharacter' => $request->getCosplayCharacter(),
            'designNotes' => $request->getDesignNotes(),
            'estimatedCost' => $request->getEstimatedCost(),
            'status' => $request->getStatus(),
            'statusLabel' => $request->getStatusLabel(),
            'createdAt' => $request->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $request->getUpdatedAt()?->format(DATE_ATOM),
            'progressSteps' => $progressSteps,
            'canTrackProgress' => !in_array($request->getStatus(), ['new_request', 'rejected'], true),
        ];
    }

    /** @return list<array{key: string, label: string, completed: bool, current: bool}> */
    private function customRequestProgressSteps(string $status): array
    {
        $flow = [
            'new_request' => 'Submitted',
            'quote_sent' => 'Quote Sent',
            'awaiting_approval' => 'Awaiting Your Approval',
            'approved' => 'Approved',
            'converted_to_order' => 'Order Placed',
        ];

        $rejected = $status === 'rejected';
        $keys = array_keys($flow);
        $currentIndex = array_search($status, $keys, true);

        if ($rejected) {
            return [
                ['key' => 'submitted', 'label' => 'Submitted', 'completed' => true, 'current' => false],
                ['key' => 'rejected', 'label' => 'Rejected', 'completed' => true, 'current' => true],
            ];
        }

        $steps = [];
        foreach ($flow as $key => $label) {
            $index = array_search($key, $keys, true);
            $steps[] = [
                'key' => $key,
                'label' => $label,
                'completed' => $currentIndex !== false && $index !== false && $index < $currentIndex,
                'current' => $key === $status,
            ];
        }

        return $steps;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

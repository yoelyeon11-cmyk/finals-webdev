<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\CustomCosplayRequestRepository;
use App\Repository\InventoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductsRepository;
use App\Service\AdminRealtimeHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/realtime')]
#[IsGranted('ROLE_STAFF')]
final class RealtimeController extends AbstractController
{
    public function __construct(
        private readonly AdminRealtimeHelper $realtime,
    ) {
    }

    #[Route('/updates', name: 'admin_realtime_updates', methods: ['GET'])]
    public function updates(
        OrderRepository $orders,
        CustomCosplayRequestRepository $customRequests,
        ProductsRepository $products,
        CategoryRepository $categories,
        InventoryRepository $inventory,
    ): JsonResponse {
        $latestOrder = $orders->findOneBy([], ['id' => 'DESC']);
        $latestCustomRequest = $customRequests->findOneBy([], ['id' => 'DESC']);
        $latestProduct = $products->findOneBy([], ['id' => 'DESC']);

        return $this->json([
            'success' => true,
            'data' => [
                'latestOrderId' => $latestOrder?->getId(),
                'latestOrderUpdatedAt' => $latestOrder?->getUpdatedAt()?->format(DATE_ATOM),
                'latestCustomRequestId' => $latestCustomRequest?->getId(),
                'latestCustomRequestUpdatedAt' => $latestCustomRequest?->getUpdatedAt()?->format(DATE_ATOM),
                'latestProductId' => $latestProduct?->getId(),
                'productsFingerprint' => $this->realtime->productsFingerprint($products),
                'categoriesFingerprint' => $this->realtime->categoriesFingerprint($categories),
                'inventoryFingerprint' => $this->realtime->inventoryFingerprint($inventory, $products),
                'dashboardFingerprint' => $this->realtime->dashboardFingerprint($products, $categories),
                'verificationFingerprint' => $this->realtime->verificationFingerprint($customRequests),
            ],
            'error' => null,
        ]);
    }

    #[Route('/inventory.json', name: 'admin_realtime_inventory_json', methods: ['GET'])]
    public function inventoryJson(
        InventoryRepository $inventory,
        ProductsRepository $products,
    ): JsonResponse {
        return $this->json([
            'success' => true,
            'data' => [
                'fingerprint' => $this->realtime->inventoryFingerprint($inventory, $products),
                'rows' => $this->realtime->serializeInventoryRows($inventory),
            ],
            'error' => null,
        ]);
    }

    #[Route('/categories.json', name: 'admin_realtime_categories_json', methods: ['GET'])]
    public function categoriesJson(CategoryRepository $categories): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'fingerprint' => $this->realtime->categoriesFingerprint($categories),
                'rows' => $this->realtime->serializeCategoryRows($categories),
            ],
            'error' => null,
        ]);
    }

    #[Route('/verification.json', name: 'admin_realtime_verification_json', methods: ['GET'])]
    public function verificationJson(CustomCosplayRequestRepository $customRequests): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'fingerprint' => $this->realtime->verificationFingerprint($customRequests),
                'rows' => $this->realtime->serializeVerificationRows($customRequests),
            ],
            'error' => null,
        ]);
    }
}

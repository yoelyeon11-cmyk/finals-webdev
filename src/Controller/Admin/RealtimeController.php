<?php

namespace App\Controller\Admin;

use App\Repository\CustomCosplayRequestRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/realtime')]
#[IsGranted('ROLE_STAFF')]
final class RealtimeController extends AbstractController
{
    #[Route('/updates', name: 'admin_realtime_updates', methods: ['GET'])]
    public function updates(
        OrderRepository $orders,
        CustomCosplayRequestRepository $customRequests,
        ProductsRepository $products,
    ): JsonResponse {
        $latestOrder = $orders->findOneBy([], ['id' => 'DESC']);
        $latestCustomRequest = $customRequests->findOneBy([], ['id' => 'DESC']);
        $latestProduct = $products->findOneBy([], ['id' => 'DESC']);

        $productParts = [];
        foreach ($products->findBy([], ['id' => 'ASC']) as $product) {
            $productParts[] = implode(':', [
                $product->getId(),
                $product->getStock() ?? 0,
                $product->getPrice(),
                $product->getName(),
            ]);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'latestOrderId' => $latestOrder?->getId(),
                'latestOrderUpdatedAt' => $latestOrder?->getUpdatedAt()?->format(DATE_ATOM),
                'latestCustomRequestId' => $latestCustomRequest?->getId(),
                'latestCustomRequestUpdatedAt' => $latestCustomRequest?->getUpdatedAt()?->format(DATE_ATOM),
                'latestProductId' => $latestProduct?->getId(),
                'productsFingerprint' => hash('sha256', implode('|', $productParts)),
            ],
            'error' => null,
        ]);
    }
}

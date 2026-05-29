<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use App\Service\AdminRealtimeHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products')]
#[IsGranted('ROLE_STAFF')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly AdminRealtimeHelper $realtime,
    ) {
    }

    #[Route('/cards.json', name: 'admin_product_cards_json', methods: ['GET'])]
    public function cardsJson(ProductsRepository $productRepository, Request $request): JsonResponse
    {
        $products = $productRepository->findBy([], ['id' => 'ASC']);

        return $this->json([
            'success' => true,
            'data' => [
                'fingerprint' => $this->realtime->productsFingerprint($productRepository),
                'cards' => array_map(
                    fn (Products $product) => $this->realtime->serializeAdminProductCard($product, $request),
                    $products,
                ),
            ],
            'error' => null,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    #[Route('/row/{id}', name: 'admin_product_row_json', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function rowJson(Products $product, Request $request): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->realtime->serializeAdminProductCard($product, $request),
            'error' => null,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}

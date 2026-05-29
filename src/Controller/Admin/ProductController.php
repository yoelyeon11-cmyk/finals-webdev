<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use App\Service\ProductCatalogRealtimeHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * JSON endpoints used by the live admin product grid.
 * Create/edit/delete live in App\Controller\ProductsController.
 */
#[Route('/admin/products')]
#[IsGranted('ROLE_STAFF')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductCatalogRealtimeHelper $catalogRealtime,
    ) {
    }

    #[Route('/cards.json', name: 'admin_product_cards_json', methods: ['GET'])]
    public function cardsJson(ProductsRepository $productRepository, Request $request): JsonResponse
    {
        $products = $productRepository->findBy([], ['id' => 'ASC']);

        return $this->json([
            'success' => true,
            'data' => [
                'fingerprint' => $this->catalogRealtime->fingerprint($productRepository),
                'cards' => array_map(
                    fn (Products $product) => $this->catalogRealtime->serializeCard($product, $request),
                    $products,
                ),
            ],
            'error' => null,
        ]);
    }

    #[Route('/row/{id}', name: 'admin_product_row_json', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function rowJson(Products $product, Request $request): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->catalogRealtime->serializeCard($product, $request),
            'error' => null,
        ]);
    }
}

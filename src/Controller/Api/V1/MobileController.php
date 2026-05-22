<?php

namespace App\Controller\Api\V1;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use App\Service\ProductImageUrlResolver;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
final class MobileController extends AbstractController
{
    public function __construct(
        private readonly ProductImageUrlResolver $imageUrlResolver,
    ) {
    }
    #[Route('/products', name: 'api_v1_products_index', methods: ['GET'])]
    public function products(Request $request, ProductsRepository $productsRepository): JsonResponse
    {
        $products = $productsRepository->findAll();

        return $this->json([
            'success' => true,
            'data' => array_map(fn (Products $p) => $this->serializeProduct($p, $request), $products),
            'error' => null,
        ]);
    }

    #[Route('/products/{id}', name: 'api_v1_products_show', methods: ['GET'])]
    public function product(#[MapEntity] Products $product, Request $request): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->serializeProduct($product, $request),
            'error' => null,
        ]);
    }

    /** @return array<string, mixed> */
    private function serializeProduct(Products $product, Request $request): array
    {
        $image = $this->imageUrlResolver->resolve($product->getImage(), $request);

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'image' => $image,
            'stock' => $product->getStock(),
            'category' => $product->getCategory() ? [
                'id' => $product->getCategory()->getId(),
                'name' => $product->getCategory()->getName(),
            ] : null,
        ];
    }

}


<?php

namespace App\Service;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ProductCatalogRealtimeHelper
{
    public function __construct(
        private readonly ProductImageUrlResolver $imageUrlResolver,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function websocketUrl(): string
    {
        return trim((string) ($_ENV['APP_WS_URL'] ?? $_SERVER['APP_WS_URL'] ?? ''), " \t\n\r\0\x0B\"'");
    }

    public function fingerprint(ProductsRepository $productRepository): string
    {
        $parts = [];
        foreach ($productRepository->findBy([], ['id' => 'ASC']) as $product) {
            $parts[] = implode(':', [
                $product->getId(),
                $product->getStock() ?? 0,
                $product->getPrice(),
                $product->getName(),
            ]);
        }

        return hash('sha256', implode('|', $parts));
    }

    /** @return array<string, mixed> */
    public function serializeCard(Products $product, Request $request): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => number_format((float) $product->getPrice(), 2, '.', ''),
            'stock' => $product->getStock() ?? 0,
            'categoryName' => $product->getCategory()?->getName() ?? 'No Category',
            'imageUrl' => $this->imageUrlResolver->resolve($product->getImage(), $request),
            'showUrl' => $this->urlGenerator->generate('admin_product_show', ['id' => $product->getId()]),
            'editUrl' => $this->urlGenerator->generate('admin_product_edit', ['id' => $product->getId()]),
            'deleteUrl' => $this->urlGenerator->generate('admin_product_delete', ['id' => $product->getId()]),
            'deleteToken' => $this->csrfTokenManager->getToken('delete'.$product->getId())->getValue(),
        ];
    }
}

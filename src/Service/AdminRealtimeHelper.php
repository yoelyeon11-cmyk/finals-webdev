<?php

namespace App\Service;

use App\Entity\CustomCosplayRequest;
use App\Entity\Products;
use App\Repository\CategoryRepository;
use App\Repository\CustomCosplayRequestRepository;
use App\Repository\InventoryRepository;
use App\Repository\ProductsRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AdminRealtimeHelper
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

    public function productsFingerprint(ProductsRepository $productRepository): string
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

    public function categoriesFingerprint(CategoryRepository $categoryRepository): string
    {
        $parts = [];
        foreach ($categoryRepository->findBy([], ['id' => 'ASC']) as $category) {
            $parts[] = implode(':', [
                $category->getId(),
                $category->getName(),
                $category->getProducts()->count(),
            ]);
        }

        return hash('sha256', implode('|', $parts));
    }

    public function inventoryFingerprint(InventoryRepository $inventoryRepository, ProductsRepository $productsRepository): string
    {
        $entries = $inventoryRepository->findBy([], ['id' => 'DESC']);
        $grouped = [];
        foreach ($entries as $entry) {
            $productId = $entry->getProduct()->getId();
            if (!isset($grouped[$productId])) {
                $grouped[$productId] = [
                    'stock' => $entry->getProduct()->getStock() ?? 0,
                    'totalAdded' => 0,
                    'lastAdded' => 0,
                ];
            }
            $grouped[$productId]['totalAdded'] += $entry->getQuantity();
            $timestamp = $entry->getCreatedAt()?->getTimestamp() ?? 0;
            if ($timestamp > $grouped[$productId]['lastAdded']) {
                $grouped[$productId]['lastAdded'] = $timestamp;
            }
        }

        foreach ($productsRepository->findBy([], ['id' => 'ASC']) as $product) {
            $id = $product->getId();
            if (!isset($grouped[$id])) {
                $grouped[$id] = ['stock' => $product->getStock() ?? 0, 'totalAdded' => 0, 'lastAdded' => 0];
            } else {
                $grouped[$id]['stock'] = $product->getStock() ?? 0;
            }
        }

        ksort($grouped);
        $parts = [];
        foreach ($grouped as $productId => $data) {
            $parts[] = implode(':', [$productId, $data['stock'], $data['totalAdded'], $data['lastAdded']]);
        }

        return hash('sha256', implode('|', $parts));
    }

    public function verificationFingerprint(CustomCosplayRequestRepository $repository): string
    {
        $pending = $repository->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', ['new_request', 'awaiting_approval', 'quote_sent'])
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();

        $parts = [];
        /** @var CustomCosplayRequest $request */
        foreach ($pending as $request) {
            $parts[] = implode(':', [
                $request->getId(),
                $request->getStatus(),
                $request->getEstimatedCost() ?? '',
                $request->getUpdatedAt()?->getTimestamp() ?? 0,
            ]);
        }

        return hash('sha256', implode('|', $parts));
    }

    /** @return list<array<string, mixed>> */
    public function serializeInventoryRows(InventoryRepository $inventoryRepository): array
    {
        $entries = $inventoryRepository->findBy([], ['id' => 'DESC']);
        $grouped = [];
        foreach ($entries as $entry) {
            $product = $entry->getProduct();
            $productId = $product->getId();
            if (!isset($grouped[$productId])) {
                $grouped[$productId] = [
                    'productId' => $productId,
                    'productName' => $product->getName(),
                    'currentStock' => $product->getStock() ?? 0,
                    'totalQuantity' => 0,
                    'lastAdded' => $entry->getCreatedAt()?->format('Y-m-d H:i'),
                    'lastAddedSort' => $entry->getCreatedAt()?->getTimestamp() ?? 0,
                ];
            }
            $grouped[$productId]['totalQuantity'] += $entry->getQuantity();
            $grouped[$productId]['currentStock'] = $product->getStock() ?? 0;
            $timestamp = $entry->getCreatedAt()?->getTimestamp() ?? 0;
            if ($timestamp > $grouped[$productId]['lastAddedSort']) {
                $grouped[$productId]['lastAddedSort'] = $timestamp;
                $grouped[$productId]['lastAdded'] = $entry->getCreatedAt()?->format('Y-m-d H:i');
            }
        }

        usort($grouped, static fn (array $a, array $b) => $b['lastAddedSort'] <=> $a['lastAddedSort']);

        return array_values($grouped);
    }

    /** @return list<array<string, mixed>> */
    public function serializeCategoryRows(CategoryRepository $categoryRepository): array
    {
        $rows = [];
        foreach ($categoryRepository->findBy([], ['name' => 'ASC']) as $category) {
            $productNames = [];
            foreach ($category->getProducts() as $product) {
                $productNames[] = $product->getName();
            }

            $rows[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'productNames' => $productNames,
                'productCount' => count($productNames),
                'editUrl' => $this->urlGenerator->generate('admin_category_edit', ['id' => $category->getId()]),
                'deleteUrl' => $this->urlGenerator->generate('admin_category_delete', ['id' => $category->getId()]),
                'deleteToken' => $this->csrfTokenManager->getToken('delete'.$category->getId())->getValue(),
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function serializeVerificationRows(CustomCosplayRequestRepository $repository): array
    {
        $pending = $repository->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', ['new_request', 'awaiting_approval', 'quote_sent'])
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $rows = [];
        /** @var CustomCosplayRequest $request */
        foreach ($pending as $request) {
            $status = (string) $request->getStatus();
            $statusBadge = match ($status) {
                'new_request' => ['class' => 'bg-primary', 'label' => 'New Request'],
                'awaiting_approval' => ['class' => 'bg-warning', 'label' => 'Awaiting Approval'],
                'quote_sent' => ['class' => 'bg-info', 'label' => 'Quote Sent'],
                default => ['class' => 'bg-secondary', 'label' => $request->getStatusLabel()],
            };

            $rows[] = [
                'id' => $request->getId(),
                'customerName' => $request->getCustomerName(),
                'cosplayCharacter' => $request->getCosplayCharacter() ?: 'Custom Design',
                'estimatedCost' => $request->getEstimatedCost(),
                'status' => $status,
                'statusBadgeClass' => $statusBadge['class'],
                'statusLabel' => $statusBadge['label'],
                'createdAt' => $request->getCreatedAt()?->format('M d, Y'),
                'reviewUrl' => $this->urlGenerator->generate('admin_verification_review', ['id' => $request->getId()]),
                'approveUrl' => $this->urlGenerator->generate('admin_verification_approve', ['id' => $request->getId()]),
                'rejectUrl' => $this->urlGenerator->generate('admin_verification_reject', ['id' => $request->getId()]),
                'approveToken' => $this->csrfTokenManager->getToken('approve_'.$request->getId())->getValue(),
                'rejectToken' => $this->csrfTokenManager->getToken('reject_'.$request->getId())->getValue(),
            ];
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    public function serializeAdminProductCard(Products $product, Request $request): array
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

<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\ProductsRepository;
use App\Service\AdminRealtimeHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_STAFF')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AdminRealtimeHelper $catalogRealtime,
    ) {
    }

    #[Route('/', name: 'admin_dashboard')]
    public function index(
        ProductsRepository $productRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        $latestProduct = $productRepository->findOneBy([], ['id' => 'DESC']);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $this->buildStats($productRepository, $categoryRepository),
            'recentProducts' => $productRepository->findBy([], ['id' => 'DESC']),
            'latestProductId' => $latestProduct?->getId(),
            'productsFingerprint' => $this->catalogRealtime->productsFingerprint($productRepository),
            'categoriesFingerprint' => $this->catalogRealtime->categoriesFingerprint($categoryRepository),
            'dashboardFingerprint' => $this->catalogRealtime->dashboardFingerprint($productRepository, $categoryRepository),
            'websocketUrl' => $this->catalogRealtime->websocketUrl(),
        ]);
    }

    #[Route('/stats.json', name: 'admin_dashboard_stats_json', methods: ['GET'])]
    public function statsJson(
        ProductsRepository $productRepository,
        CategoryRepository $categoryRepository,
    ): JsonResponse {
        $recentProducts = $productRepository->findBy([], ['id' => 'DESC'], 50);

        return $this->json([
            'success' => true,
            'data' => [
                'stats' => $this->buildStats($productRepository, $categoryRepository),
                'productsFingerprint' => $this->catalogRealtime->productsFingerprint($productRepository),
                'categoriesFingerprint' => $this->catalogRealtime->categoriesFingerprint($categoryRepository),
                'dashboardFingerprint' => $this->catalogRealtime->dashboardFingerprint($productRepository, $categoryRepository),
                'latestProductId' => $productRepository->findOneBy([], ['id' => 'DESC'])?->getId(),
                'recentProducts' => array_map(
                    static fn ($product) => [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'categoryName' => $product->getCategory()?->getName() ?? 'No Category',
                        'stock' => $product->getStock() ?? 0,
                        'price' => (float) $product->getPrice(),
                    ],
                    $recentProducts,
                ),
            ],
            'error' => null,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /** @return array<string, int|float> */
    private function buildStats(
        ProductsRepository $productRepository,
        CategoryRepository $categoryRepository,
    ): array {
        $lowStockCount = (int) $productRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.stock <= :lowStock')
            ->setParameter('lowStock', 10)
            ->getQuery()
            ->getSingleScalarResult();

        $totalValue = (float) ($productRepository->createQueryBuilder('p')
            ->select('SUM(p.price * p.stock)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        return [
            'totalProducts' => $productRepository->count([]),
            'totalCategories' => $categoryRepository->count([]),
            'lowStockCount' => $lowStockCount,
            'totalValue' => $totalValue,
        ];
    }
}
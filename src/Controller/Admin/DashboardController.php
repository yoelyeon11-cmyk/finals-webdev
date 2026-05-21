<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use App\Repository\CategoryRepository;  // Add this import
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_STAFF')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        ProductsRepository $productRepository,
        CategoryRepository $categoryRepository  // Add this parameter
    ): Response {
        // Get actual counts from database
        $totalProducts = $productRepository->count([]);
        $totalCategories = $categoryRepository->count([]);  // Real category count
        
        // Count low stock items (stock <= 10)
        $lowStockCount = $productRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.stock <= :lowStock')
            ->setParameter('lowStock', 10)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Calculate total inventory value (price * stock)
        $totalValue = $productRepository->createQueryBuilder('p')
            ->select('SUM(p.price * p.stock)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        $stats = [
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'lowStockCount' => $lowStockCount,
            'totalValue' => $totalValue,
            'productsGrowth' => 12,  // You can calculate this later based on timestamps
            'valueGrowth' => 8,
            'newCategories' => 2,
        ];

        $recentProducts = $productRepository->findBy([], ['id' => 'DESC']);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'recentProducts' => $recentProducts,
        ]);
    }
}
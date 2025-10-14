<?php

namespace App\Controller\Admin;

use App\Entity\Products;  // ← Change to Products
use App\Repository\ProductsRepository;  // ← Change to ProductsRepository
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(ProductsRepository $productRepository): Response  // ← ProductsRepository
    {
        $stats = [
            'totalProducts' => $productRepository->count([]),
            'totalCategories' => 0,  // Update when you add CategoryRepository
            'lowStockCount' => 0,
            'totalValue' => 0,
            'productsGrowth' => 12,
            'valueGrowth' => 8,
            'newCategories' => 2,
        ];

        $recentProducts = $productRepository->findBy([], ['id' => 'DESC'], 10);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'recentProducts' => $recentProducts,
        ]);
    }
}
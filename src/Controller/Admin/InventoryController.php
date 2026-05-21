<?php

namespace App\Controller\Admin;

use App\Entity\Inventory;
use App\Form\InventoryType;
use App\Repository\InventoryRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/inventory')]
#[IsGranted('ROLE_STAFF')]
class InventoryController extends AbstractController
{
    #[Route('/', name: 'admin_inventory_index', methods: ['GET'])]
    public function index(InventoryRepository $inventoryRepository): Response
    {
        // Get all inventory entries grouped by product
        $entries = $inventoryRepository->findBy([], ['id' => 'DESC']);
        
        // Group entries by product and sum quantities
        $groupedEntries = [];
        foreach ($entries as $entry) {
            $productId = $entry->getProduct()->getId();
            if (!isset($groupedEntries[$productId])) {
                $groupedEntries[$productId] = [
                    'product' => $entry->getProduct(),
                    'totalQuantity' => 0,
                    'lastAdded' => $entry->getCreatedAt(),
                ];
            }
            $groupedEntries[$productId]['totalQuantity'] += $entry->getQuantity();
            // Keep the most recent date
            if ($entry->getCreatedAt() > $groupedEntries[$productId]['lastAdded']) {
                $groupedEntries[$productId]['lastAdded'] = $entry->getCreatedAt();
            }
        }
        
        // Sort by last added date (newest first)
        usort($groupedEntries, function ($a, $b) {
            return $b['lastAdded'] <=> $a['lastAdded'];
        });
        
        return $this->render('admin/inventory/index.html.twig', [
            'entries' => $groupedEntries,
        ]);
    }

    #[Route('/add', name: 'admin_inventory_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $inventory = new Inventory();
        $form = $this->createForm(InventoryType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update product stock
            $product = $inventory->getProduct();
            $product->setStock(($product->getStock() ?? 0) + $inventory->getQuantity());

            // Persist inventory record
            $entityManager->persist($inventory);
            $entityManager->flush();

            $logger->log('Inventory Stock Added', 'Added ' . $inventory->getQuantity() . ' units to ' . $product->getName());
            $this->addFlash('success', 'Stock added successfully!');

            return $this->redirectToRoute('admin_inventory_index');
        }

        return $this->render('admin/inventory/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

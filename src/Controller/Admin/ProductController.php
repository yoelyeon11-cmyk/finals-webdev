<?php
// src/Controller/Admin/ProductController.php

namespace App\Controller\Admin;

use App\Entity\Products;
use App\Form\ProductsType;
use App\Repository\ProductsRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/products')]
#[IsGranted('ROLE_STAFF')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'admin_product_index')]
    public function index(ProductsRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('admin/product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'admin_product_new')]
    #[IsGranted('ROLE_STAFF')]
    public function new(Request $request, EntityManagerInterface $em, ActivityLogger $logger, LoggerInterface $consoleLogger): Response
    {
        $product = new Products();
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();

            $consoleLogger->info('🟢 PRODUCT CREATED - Name: ' . $product->getName() . ', ID: ' . $product->getId() . ', User: ' . $this->getUser()->getUserIdentifier());
            $logger->log('Product Created', 'Created product: ' . $product->getName() . ' (ID: ' . $product->getId() . ')');
            $consoleLogger->info('🟢 ACTIVITY LOG CALLED for Product Created');
            $this->addFlash('success', 'Product created successfully!');
            $this->addFlash('console_debug', '🟢 PRODUCT CREATED - Name: ' . $product->getName() . ', ID: ' . $product->getId() . ', User: ' . $this->getUser()->getUserIdentifier());
            $this->addFlash('console_debug', '🟢 ActivityLogger.log() called for Product Created');
            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/new.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }
#[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Products $product, EntityManagerInterface $entityManager, ActivityLogger $logger, LoggerInterface $consoleLogger): Response
{
    // Check CSRF token manually for POST requests
    if ($request->isMethod('POST')) {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('products', $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }
    }

    $form = $this->createForm(ProductsType::class, $product);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        /** @var UploadedFile|null $imageFile */
        $imageFile = $form->get('imageFile')->getData();
        if ($imageFile) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0775, true);
            }
            $safeFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = uniqid() . '-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $safeFilename) . '.' . $imageFile->guessExtension();
            $imageFile->move($uploadsDir, $newFilename);
            $product->setImage('/uploads/products/' . $newFilename);
        }
        $entityManager->flush();
        
        $consoleLogger->info('🟡 PRODUCT UPDATED - Name: ' . $product->getName() . ', ID: ' . $product->getId() . ', User: ' . $this->getUser()->getUserIdentifier());
        $logger->log('Product Updated', 'Updated product: ' . $product->getName() . ' (ID: ' . $product->getId() . ')');
        $consoleLogger->info('🟡 ACTIVITY LOG CALLED for Product Updated');
        $this->addFlash('success', 'Product updated successfully!');
        $this->addFlash('console_debug', '🟡 PRODUCT UPDATED - Name: ' . $product->getName() . ', ID: ' . $product->getId() . ', User: ' . $this->getUser()->getUserIdentifier());
        $this->addFlash('console_debug', '🟡 ActivityLogger.log() called for Product Updated');

        return $this->redirectToRoute('admin_product_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('products/edit.html.twig', [
        'product' => $product,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'admin_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function delete(Request $request, Products $product, EntityManagerInterface $em, ActivityLogger $logger, LoggerInterface $consoleLogger): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $productName = $product->getName();
            $productId = $product->getId();
            $em->remove($product);
            $em->flush();
            $consoleLogger->info('🔴 PRODUCT DELETED - Name: ' . $productName . ', ID: ' . $productId . ', User: ' . $this->getUser()->getUserIdentifier());
            $logger->log('Product Deleted', 'Deleted product: ' . $productName);
            $consoleLogger->info('🔴 ACTIVITY LOG CALLED for Product Deleted');
            $this->addFlash('success', 'Product deleted successfully!');
            $this->addFlash('console_debug', '🔴 PRODUCT DELETED - Name: ' . $productName . ', ID: ' . $productId . ', User: ' . $this->getUser()->getUserIdentifier());
            $this->addFlash('console_debug', '🔴 ActivityLogger.log() called for Product Deleted');
        }

        return $this->redirectToRoute('admin_product_index');
    }
}
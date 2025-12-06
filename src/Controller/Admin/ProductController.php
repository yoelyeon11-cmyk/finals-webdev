<?php
// src/Controller/Admin/ProductController.php

namespace App\Controller\Admin;

use App\Entity\Products;
use App\Form\ProductType;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/products')]
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
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Products();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/new.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }
#[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Products $product, EntityManagerInterface $entityManager): Response
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
        
        $this->addFlash('success', 'Product updated successfully!');

        return $this->redirectToRoute('admin_product_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('products/edit.html.twig', [
        'product' => $product,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(Request $request, Products $product, EntityManagerInterface $em): Response  // ← Changed Product to Products
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Product deleted successfully!');
        }

        return $this->redirectToRoute('admin_product_index');
    }
}
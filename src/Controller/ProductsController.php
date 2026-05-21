<?php

namespace App\Controller;

use App\Entity\Products;
use App\Form\ProductsType;
use App\Repository\ProductsRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products')]
#[IsGranted('ROLE_STAFF')]
final class ProductsController extends AbstractController
{
    #[Route(name: 'admin_product_index', methods: ['GET'])]
    public function index(ProductsRepository $productsRepository): Response
    {
        return $this->render('products/index.html.twig', [
            'products' => $productsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_product_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $product = new Products();
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $product);
            $entityManager->persist($product);
            $entityManager->flush();

            $logger->log('Product Created', 'Created product: ' . $product->getName() . ' (ID: ' . $product->getId() . ')');
            $this->addFlash('success', 'Product created successfully!');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('products/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Products $product, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $product);
            $entityManager->flush();

            $logger->log('Product Updated', 'Updated product: ' . $product->getName() . ' (ID: ' . $product->getId() . ')');
            $this->addFlash('success', 'Product updated successfully!');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('products/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function delete(Request $request, Products $product, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $productName = $product->getName();
            $productId = $product->getId();
            $entityManager->remove($product);
            $entityManager->flush();

            $logger->log('Product Deleted', 'Deleted product: ' . $productName . ' (ID: ' . $productId . ')');
            $this->addFlash('success', 'Product deleted successfully!');
        }

        return $this->redirectToRoute('admin_product_index');
    }

    #[Route('/{id}', name: 'admin_product_show', methods: ['GET'])]
    public function show(Products $product): Response
    {
        return $this->render('products/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/search', name: 'admin_product_search', methods: ['GET'])]
    public function search(Request $request, ProductsRepository $productsRepository): Response
    {
        $query = trim($request->query->get('q', ''));
        $products = $productsRepository->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE LOWER(:query)')
            ->orWhere('LOWER(p.description) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();

        return $this->json(array_map(fn($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'price' => $p->getPrice(),
            'image' => $p->getImage(),
        ], $products));
    }

    // ------------------------
    // Image upload helper
    // ------------------------
    private function handleImageUpload(FormInterface $form, Products $product): void
    {
        /** @var UploadedFile|null $imageFile */
        $imageFile = $form->get('imageFile')->getData();
        if (!$imageFile) {
            return;
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0775, true);
        }

        $safeFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = bin2hex(random_bytes(6)) . '-' .
            preg_replace('/[^A-Za-z0-9_-]/', '-', $safeFilename) . '.' .
            $imageFile->guessExtension();

        $imageFile->move($uploadsDir, $newFilename);

        $product->setImage('/uploads/products/' . $newFilename);
    }
}

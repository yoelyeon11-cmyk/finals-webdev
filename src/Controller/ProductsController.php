<?php

namespace App\Controller;

use App\Entity\Products;
use App\Form\ProductsType;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products')]
final class ProductsController extends AbstractController
{
    #[Route(name: 'app_products_index', methods: ['GET'])]
    public function index(ProductsRepository $productsRepository): Response
    {
        return $this->render('products/index.html.twig', [
            'products' => $productsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_products_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Products();
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_products_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('products/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    // ✅ Move search routes ABOVE the {id} route
    #[Route('/search', name: 'app_products_search', methods: ['GET'])]
    public function search(Request $request, ProductsRepository $productsRepository): Response
    {
        $query = $request->query->get('q', '');

        $products = $productsRepository->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE LOWER(:query)')
            ->orWhere('LOWER(p.description) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'image' => $product->getImage(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/results', name: 'app_products_search_results', methods: ['GET'])]
    public function searchResults(Request $request, ProductsRepository $productsRepository): Response
    {
        $query = $request->query->get('q', '');

        $products = $productsRepository->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE LOWER(:query)')
            ->orWhere('LOWER(p.description) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();

        return $this->render('products/search_results.html.twig', [
            'products' => $products,
            'query' => $query,
        ]);
    }

    // 👇 Keep this BELOW
    #[Route('/{id}', name: 'app_products_show', methods: ['GET'])]
    public function show(Products $product): Response
    {
        return $this->render('products/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_products_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Products $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_products_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('products/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_products_delete', methods: ['POST'])]
    public function delete(Request $request, Products $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_products_index', [], Response::HTTP_SEE_OTHER);
    }
}

<?php

namespace App\Controller;

use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomePageController extends AbstractController
{
    #[Route('/home/page', name: 'app_home_page')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $products = [];

        if ($query !== '') {
            $products = $entityManager->getRepository(Products::class)
                ->createQueryBuilder('p')
                ->where('LOWER(p.name) LIKE LOWER(:query)')
                ->setParameter('query', '%' . $query . '%')
                ->getQuery()
                ->getResult();
        }

        return $this->render('home_page/index.html.twig', [
            'products' => $products,
            'query' => $query,
        ]);
    }
}

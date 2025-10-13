<?php

namespace App\Controller;

use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomePageController extends AbstractController
{
    #[Route('/home/page', name: 'app_home_page')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $request->query->get('q', '');
        $products = [];

        if (!empty($query)) {
            $products = $entityManager->getRepository(Products::class)
                ->createQueryBuilder('p')
                ->where('LOWER(p.name) LIKE LOWER(:query)')
                ->setParameter('query', '%' . $query . '%')
                ->getQuery()
                ->getResult();
        }

        return $this->render('home_page/index.html.twig', [
            'controller_name' => 'HomePageController',
            'products' => $products,
            'query' => $query,
        ]);
    }
}

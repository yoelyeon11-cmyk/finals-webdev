<?php

namespace App\Controller;

use App\Entity\Products;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StorefrontController extends AbstractController
{
    #[Route('/products/{id}', name: 'app_product_show', requirements: ['id' => '\d+'])]
    public function show(Products $product): Response
    {
        return $this->render('storefront/product_show.html.twig', [
            'product' => $product,
        ]);
    }
}

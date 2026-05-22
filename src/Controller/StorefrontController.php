<?php

namespace App\Controller;

use App\Entity\Products;
use App\Service\ProductImageUrlResolver;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StorefrontController extends AbstractController
{
    public function __construct(
        private readonly ProductImageUrlResolver $imageUrlResolver,
    ) {
    }

    #[Route('/products/{id}', name: 'app_product_show', requirements: ['id' => '\d+'])]
    public function show(#[MapEntity] Products $product): Response
    {
        return $this->render('storefront/product_show.html.twig', [
            'product' => $product,
            'imageUrl' => $this->imageUrlResolver->resolve($product->getImage()),
        ]);
    }
}

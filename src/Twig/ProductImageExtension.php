<?php

namespace App\Twig;

use App\Service\ProductImageUrlResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ProductImageExtension extends AbstractExtension
{
    public function __construct(
        private readonly ProductImageUrlResolver $resolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('product_image_url', $this->resolve(...)),
        ];
    }

    public function resolve(?string $image): ?string
    {
        return $this->resolver->resolve($image);
    }
}

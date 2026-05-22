<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds absolute URLs for product images (local uploads, external URLs, legacy paths).
 */
final class ProductImageUrlResolver
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function resolve(?string $image, ?Request $request = null): ?string
    {
        if ($image === null || trim($image) === '') {
            return null;
        }

        $image = trim($image);

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        $request ??= $this->requestStack->getCurrentRequest();
        $base = $request?->getSchemeAndHttpHost() ?? '';

        if (str_starts_with($image, '/')) {
            return $base !== '' ? $base . $image : $image;
        }

        if (str_starts_with($image, 'uploads/')) {
            return $base !== '' ? $base . '/' . $image : '/' . $image;
        }

        return $base !== '' ? $base . '/uploads/products/' . ltrim($image, '/') : '/uploads/products/' . ltrim($image, '/');
    }
}

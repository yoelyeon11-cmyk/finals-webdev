<?php

namespace App\Command;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Point product images at files that exist, or fall back to bundled / CDN URLs.
 */
#[AsCommand(
    name: 'app:fix-product-images',
    description: 'Repair broken product image paths (missing uploads → fallbacks)',
)]
final class FixProductImagesCommand extends Command
{
    /** @var array<string, string> product name (lowercase) => fallback image path or URL */
    private const NAME_FALLBACKS = [
        'sailor moon' => 'https://images.unsplash.com/photo-1596462502278-27bfdd403348?w=600&q=80',
        'demon slayer' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&q=80',
        'genshin' => 'https://images.unsplash.com/photo-1612036782180-6f0b6cd4eceb?w=600&q=80',
        'miku' => '/images/collections/mikucosplay.webp',
        'jujutsu' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=600&q=80',
        'contact lens' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=600&q=80',
        'blue contact' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=600&q=80',
        'katana' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=600&q=80',
        'ribbon' => 'https://images.unsplash.com/photo-1522338140262-f46f5913618a?w=600&q=80',
        'furina' => '/images/collections/mizu5cosplay.webp',
        'regulus' => '/images/collections/columbinacosplay.webp',
        'neuvillette' => '/images/collections/mizu5cosplay.webp',
        'wing' => '/images/collections/landing-clouds.png',
        'aot' => '/images/collections/liz-b-naril.jpg',
        'capitano' => '/images/collections/wonyoung-g-wapa.jpg',
    ];

    public function __construct(
        private readonly ProductsRepository $products,
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $publicDir = $this->projectDir . '/public';
        $fixed = 0;

        foreach ($this->products->findAll() as $product) {
            $image = $product->getImage();
            if ($this->imageExists($publicDir, $image)) {
                continue;
            }

            $fallback = $this->resolveFallback($product);
            if ($fallback === null) {
                $io->warning(sprintf('No fallback for "%s" (was: %s)', $product->getName(), $image ?? 'empty'));
                continue;
            }

            $product->setImage($fallback);
            $this->em->persist($product);
            ++$fixed;
            $io->writeln(sprintf('  <info>%s</info> → %s', $product->getName(), $fallback));
        }

        $this->em->flush();
        $io->success(sprintf('Updated %d product image(s).', $fixed));

        return Command::SUCCESS;
    }

    private function imageExists(string $publicDir, ?string $image): bool
    {
        if ($image === null || trim($image) === '') {
            return false;
        }

        $image = trim($image);

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return true;
        }

        $relative = str_starts_with($image, '/') ? $image : '/uploads/products/' . $image;
        $path = $publicDir . $relative;

        return is_file($path);
    }

    private function resolveFallback(Products $product): ?string
    {
        $name = strtolower($product->getName() ?? '');

        foreach (self::NAME_FALLBACKS as $needle => $url) {
            if (str_contains($name, $needle)) {
                return $url;
            }
        }

        $files = array_merge(
            glob($this->projectDir . '/public/images/collections/*.jpg') ?: [],
            glob($this->projectDir . '/public/images/collections/*.jpeg') ?: [],
            glob($this->projectDir . '/public/images/collections/*.png') ?: [],
            glob($this->projectDir . '/public/images/collections/*.webp') ?: [],
        );

        if ($files !== []) {
            return '/images/collections/' . basename($files[0]);
        }

        return 'https://images.unsplash.com/photo-1596462502278-27bfdd403348?w=600&q=80';
    }
}

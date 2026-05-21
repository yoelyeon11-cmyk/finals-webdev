<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Products;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            'Wigs' => $this->createCategory($manager, 'Wigs'),
            'Costumes' => $this->createCategory($manager, 'Costumes'),
            'Accessories' => $this->createCategory($manager, 'Accessories'),
            'Props' => $this->createCategory($manager, 'Props'),
        ];

        $products = [
            [
                'name' => 'Sailor Moon Wig',
                'description' => 'Premium blonde twin-tail wig with soft fibers, perfect for Sailor Moon cosplay.',
                'price' => 2499.00,
                'stock' => 12,
                'category' => 'Wigs',
                'image' => 'https://images.unsplash.com/photo-1596462502278-27bfdd403348?w=600&q=80',
            ],
            [
                'name' => 'Demon Slayer Haori',
                'description' => 'Checkered green-and-black haori jacket inspired by Tanjiro Kamado.',
                'price' => 1899.00,
                'stock' => 8,
                'category' => 'Costumes',
                'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&q=80',
            ],
            [
                'name' => 'Genshin Vision Prop',
                'description' => 'LED-ready elemental vision prop with clip attachment for belts and outfits.',
                'price' => 899.00,
                'stock' => 20,
                'category' => 'Props',
                'image' => 'https://images.unsplash.com/photo-1612036782180-6f0b6cd4eceb?w=600&q=80',
            ],
            [
                'name' => 'Miku Twin-Tail Wig',
                'description' => 'Teal twin-tail wig with styling clips for Hatsune Miku and similar characters.',
                'price' => 2199.00,
                'stock' => 15,
                'category' => 'Wigs',
                'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=600&q=80',
            ],
            [
                'name' => 'Jujutsu Sorcerer Uniform',
                'description' => 'Tokyo Jujutsu High-style uniform set with jacket and pants.',
                'price' => 2799.00,
                'stock' => 6,
                'category' => 'Costumes',
                'image' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=600&q=80',
            ],
            [
                'name' => 'Cosplay Contact Lens Set',
                'description' => 'Anime-style colored lenses (pair) in multiple shades for character accuracy.',
                'price' => 599.00,
                'stock' => 30,
                'category' => 'Accessories',
                'image' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=600&q=80',
            ],
            [
                'name' => 'Katana Foam Prop',
                'description' => 'Lightweight convention-safe katana prop with matte finish.',
                'price' => 749.00,
                'stock' => 18,
                'category' => 'Props',
                'image' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=600&q=80',
            ],
            [
                'name' => 'Ribbon Hair Accessories',
                'description' => 'Assorted bows and ribbons for magical girl and idol cosplays.',
                'price' => 349.00,
                'stock' => 40,
                'category' => 'Accessories',
                'image' => 'https://images.unsplash.com/photo-1522338140262-f46f5913618a?w=600&q=80',
            ],
        ];

        foreach ($products as $data) {
            $product = new Products();
            $product->setName($data['name']);
            $product->setDescription($data['description']);
            $product->setPrice($data['price']);
            $product->setStock($data['stock']);
            $product->setImage($data['image']);
            $product->setCategory($categories[$data['category']]);
            $manager->persist($product);
        }

        $manager->flush();
    }

    private function createCategory(ObjectManager $manager, string $name): Category
    {
        $category = new Category();
        $category->setName($name);
        $manager->persist($category);

        return $category;
    }
}

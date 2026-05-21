<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ProductsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: 'true'),
        new Get(security: 'true'),
    ],
    normalizationContext: ['groups' => ['product:read']],
)]
#[ORM\Entity(repositoryClass: ProductsRepository::class)]
class Products
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['product:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['product:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[Groups(['product:read'])]
    private ?Category $category = null;

    #[ORM\Column(type: Types::INTEGER, options: ["default" => 0])]
    #[Groups(['product:read'])]
    private ?int $stock = 0;

    public function __construct()
    {
        $this->stock = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        // Ensure bidirectional sync
        if ($category !== null && !$category->getProducts()->contains($this)) {
            $category->addProduct($this);
        }

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Product';
    }
}

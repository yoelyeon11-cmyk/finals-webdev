<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use App\Repository\OrderRepository;
use App\State\OrderByTransactionProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/orders/by-transaction/{transactionId}',
            uriVariables: [
                'transactionId' => new Link(fromClass: Order::class, identifiers: ['transactionId']),
            ],
            security: 'true',
            normalizationContext: ['groups' => ['order:read']],
            provider: OrderByTransactionProvider::class,
        ),
    ],
)]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    /**
     * Defines the only allowed direction of status changes (forward-only).
     * The array order represents the progression.
     */
    public const STATUS_FLOW = [
        'new_order',
        'preparing',
        'ready_to_ship',
        'shipping',
        'delivered',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['order:read'])]
    private ?string $transactionId = null;

    // Customer Information
    #[ORM\Column(length: 255)]
    #[Groups(['order:read'])]
    private ?string $customerName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read'])]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerPhone = null;

    // Order Details
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['order:read'])]
    private ?string $itemsDescription = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['order:read'])]
    private ?string $totalAmount = null;

    // Payment & Shipping
    #[ORM\Column(length: 50)]
    #[Groups(['order:read'])]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $shippingAddress = null;

    // Tracking
    #[ORM\Column(length: 50)]
    #[Groups(['order:read'])]
    private ?string $status = 'new_order';

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $shippingCarrier = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $trackingNumber = null;

    // Timestamps
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $orderDate = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $completedAt = null;

    // Relations
    #[ORM\ManyToOne]
    private ?CustomCosplayRequest $customRequest = null;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->orderDate = new \DateTimeImmutable();
        $this->transactionId = $this->generateTransactionId();
    }

    private function generateTransactionId(): string
    {
        return 'ORD-' . strtoupper(uniqid());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): static
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): static
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;
        return $this;
    }

    public function getItemsDescription(): ?string
    {
        return $this->itemsDescription;
    }

    public function setItemsDescription(string $itemsDescription): static
    {
        $this->itemsDescription = $itemsDescription;
        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUS_FLOW, true);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        if (!self::isValidStatus($newStatus)) {
            return false;
        }

        $current = $this->status ?? '';
        // If existing data is in an unexpected/legacy state, allow moving into the defined flow.
        if (!self::isValidStatus($current)) {
            return true;
        }

        if ($current === $newStatus) {
            return true;
        }

        $currentIndex = array_search($current, self::STATUS_FLOW, true);
        $newIndex = array_search($newStatus, self::STATUS_FLOW, true);

        if ($currentIndex === false || $newIndex === false) {
            return false;
        }

        // Forward-only (cannot go back to past statuses).
        return $newIndex > $currentIndex;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        
        if ($status === 'delivered') {
            $this->completedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getShippingCarrier(): ?string
    {
        return $this->shippingCarrier;
    }

    public function setShippingCarrier(?string $shippingCarrier): static
    {
        $this->shippingCarrier = $shippingCarrier;
        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getOrderDate(): ?\DateTimeImmutable
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTimeImmutable $orderDate): static
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getCustomRequest(): ?CustomCosplayRequest
    {
        return $this->customRequest;
    }

    public function setCustomRequest(?CustomCosplayRequest $customRequest): static
    {
        $this->customRequest = $customRequest;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    #[Groups(['order:read'])]
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'new_order' => 'New Order',
            'preparing' => 'Preparing Order',
            'ready_to_ship' => 'Ready to Ship',
            'shipping' => 'Shipping',
            'delivered' => 'Delivered/Completed',
            default => 'Unknown'
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'new_order' => 'badge-info',
            'preparing' => 'badge-warning',
            'ready_to_ship' => 'badge-primary',
            'shipping' => 'badge-secondary',
            'delivered' => 'badge-success',
            default => 'badge-light'
        };
    }
}

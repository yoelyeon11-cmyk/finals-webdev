<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $transactionId = null;

    // Customer Information
    #[ORM\Column(length: 255)]
    private ?string $customerName = null;

    #[ORM\Column(length: 255)]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerPhone = null;

    // Order Details
    #[ORM\Column(type: Types::TEXT)]
    private ?string $itemsDescription = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    // Payment & Shipping
    #[ORM\Column(length: 50)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shippingAddress = null;

    // Tracking
    #[ORM\Column(length: 50)]
    private ?string $status = 'new_order';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingCarrier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingNumber = null;

    // Timestamps
    #[ORM\Column]
    private ?\DateTimeImmutable $orderDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
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

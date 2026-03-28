<?php

namespace App\Entity;

use App\Repository\CustomCosplayRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomCosplayRequestRepository::class)]
class CustomCosplayRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Customer Information
    #[ORM\Column(length: 255)]
    private ?string $customerName = null;

    #[ORM\Column(length: 255)]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerPhone = null;

    // Request Details
    #[ORM\Column(length: 255)]
    private ?string $cosplayCharacter = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $designNotes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $referenceImages = null;

    // Sizing & Measurements
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $bust = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $waist = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $hip = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $shoulderWidth = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $inseam = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $height = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customMeasurements = null;

    // Quote & Status
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedCost = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'new_request';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->referenceImages = [];
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCosplayCharacter(): ?string
    {
        return $this->cosplayCharacter;
    }

    public function setCosplayCharacter(string $cosplayCharacter): static
    {
        $this->cosplayCharacter = $cosplayCharacter;
        return $this;
    }

    public function getDesignNotes(): ?string
    {
        return $this->designNotes;
    }

    public function setDesignNotes(?string $designNotes): static
    {
        $this->designNotes = $designNotes;
        return $this;
    }

    public function getReferenceImages(): ?array
    {
        return $this->referenceImages;
    }

    public function setReferenceImages(?array $referenceImages): static
    {
        $this->referenceImages = $referenceImages;
        return $this;
    }

    public function getBust(): ?string
    {
        return $this->bust;
    }

    public function setBust(?string $bust): static
    {
        $this->bust = $bust;
        return $this;
    }

    public function getWaist(): ?string
    {
        return $this->waist;
    }

    public function setWaist(?string $waist): static
    {
        $this->waist = $waist;
        return $this;
    }

    public function getHip(): ?string
    {
        return $this->hip;
    }

    public function setHip(?string $hip): static
    {
        $this->hip = $hip;
        return $this;
    }

    public function getShoulderWidth(): ?string
    {
        return $this->shoulderWidth;
    }

    public function setShoulderWidth(?string $shoulderWidth): static
    {
        $this->shoulderWidth = $shoulderWidth;
        return $this;
    }

    public function getInseam(): ?string
    {
        return $this->inseam;
    }

    public function setInseam(?string $inseam): static
    {
        $this->inseam = $inseam;
        return $this;
    }

    public function getHeight(): ?string
    {
        return $this->height;
    }

    public function setHeight(?string $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function getCustomMeasurements(): ?string
    {
        return $this->customMeasurements;
    }

    public function setCustomMeasurements(?string $customMeasurements): static
    {
        $this->customMeasurements = $customMeasurements;
        return $this;
    }

    public function getEstimatedCost(): ?string
    {
        return $this->estimatedCost;
    }

    public function setEstimatedCost(?string $estimatedCost): static
    {
        $this->estimatedCost = $estimatedCost;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
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
            'new_request' => 'New Request',
            'quote_sent' => 'Quote Sent',
            'awaiting_approval' => 'Awaiting Customer Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'converted_to_order' => 'Converted to Order',
            default => 'Unknown'
        };
    }
}

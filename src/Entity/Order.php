<?php
// src/Entity/Order.php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $buyer = null;

  #[ORM\ManyToOne(targetEntity: Listing::class)]
    #[ORM\JoinColumn(name: 'listing_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Listing $listing = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'pending';

    #[ORM\Column]
    private ?\DateTimeImmutable $orderedAt = null;

    // Historical data
    #[ORM\Column]
    private ?float $purchasePrice = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $seller = null;

    #[ORM\Column(length: 255)]
    private ?string $collectibleName = null;

    // ADD THIS: Who created the order (Admin/Staff)
    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->orderedAt = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Manila'));
    }

    // Factory method (updated with createdBy)
    public static function createFromListing(User $buyer, Listing $listing, ?User $createdBy = null): self
    {
        $order = new self();
        $order->setBuyer($buyer);
        $order->setListing($listing);
        $order->setCreatedBy($createdBy); // Set who created it
        
        // Store historical data
        $order->setSeller($listing->getUser());
        $order->setPurchasePrice($listing->getPrice());
        $order->setCollectibleName($listing->getCollectible()->getName());
        
        return $order;
    }

    // ========== GETTERS AND SETTERS ==========
    
    public function getId(): ?int { return $this->id; }
    
    public function getBuyer(): ?User { return $this->buyer; }
    public function setBuyer(?User $buyer): static { 
        $this->buyer = $buyer; 
        return $this; 
    }

    public function getListing(): ?Listing { return $this->listing; }
    public function setListing(?Listing $listing): static { 
        $this->listing = $listing; 
        return $this; 
    }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { 
        $this->status = $status; 
        return $this; 
    }

    public function getOrderedAt(): ?\DateTimeImmutable { return $this->orderedAt; }
    public function setOrderedAt(\DateTimeImmutable $orderedAt): static { 
        $this->orderedAt = $orderedAt; 
        return $this; 
    }

    public function getPurchasePrice(): ?float { return $this->purchasePrice; }
    public function setPurchasePrice(float $purchasePrice): static { 
        $this->purchasePrice = $purchasePrice; 
        return $this; 
    }

    public function getSeller(): ?User { return $this->seller; }
    public function setSeller(?User $seller): static { 
        $this->seller = $seller; 
        return $this; 
    }

    public function getCollectibleName(): ?string { return $this->collectibleName; }
    public function setCollectibleName(string $collectibleName): static { 
        $this->collectibleName = $collectibleName; 
        return $this; 
    }

    // ADD THESE: createdBy getter/setter
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    // Helper method for display
    public function getFormattedPrice(): string
    {
        return '₱' . number_format($this->purchasePrice, 2);
    }

    // Helper methods for status
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isConfirmed(): bool { return $this->status === 'confirmed'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
}
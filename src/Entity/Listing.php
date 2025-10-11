<?php

namespace App\Entity;

use App\Repository\ListingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Collectible;

#[ORM\Entity(repositoryClass: ListingRepository::class)]
class Listing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'listings')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'listings')]
    private ?Collectible $collectible = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column]
    private ?bool $is_for_sale = null;

    #[ORM\Column]
    private ?bool $is_shop_item = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime(); // initialize createdAt to current time
        $this->is_for_sale = false;         // default value
        $this->is_shop_item = false;        // default value
    }

    // -------------------------
    // Getters and Setters
    // -------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCollectible(): ?Collectible
    {
        return $this->collectible;
    }

    public function setCollectible(?Collectible $collectible): static
    {
        $this->collectible = $collectible;
        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function isForSale(): ?bool
    {
        return $this->is_for_sale;
    }

    public function setIsForSale(bool $is_for_sale): static
    {
        $this->is_for_sale = $is_for_sale;
        return $this;
    }

    public function isShopItem(): ?bool
    {
        return $this->is_shop_item;
    }

    public function setIsShopItem(bool $is_shop_item): static
    {
        $this->is_shop_item = $is_shop_item;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}

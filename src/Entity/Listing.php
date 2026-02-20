<?php

namespace App\Entity;

use App\Repository\ListingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListingRepository::class)]
class Listing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
        
  #[ORM\Column(length: 50, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(nullable: true)] // ADD nullable: true
    private ?float $price = null;

    #[ORM\Column(nullable: true)] // ADD nullable: true
    private ?bool $is_for_sale = null;

    #[ORM\ManyToOne(inversedBy: 'listings')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]  // Add this line
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'listings')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]  // Add this line
    private ?Collectible $collectible = null;

    #[ORM\ManyToOne]
    private ?user $CreatedBy = null;
        public function getId(): ?int
    {
        return $this->id;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(string $grade): static
    {
        $this->grade = $grade;

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

    public function isForSale(): ?bool
    {
        return $this->is_for_sale;
    }

    public function setIsForSale(bool $is_for_sale): static
    {
        $this->is_for_sale = $is_for_sale;

        return $this;
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

    public function getCreatedBy(): ?user
    {
        return $this->CreatedBy;
    }

    public function setCreatedBy(?user $CreatedBy): static
    {
        $this->CreatedBy = $CreatedBy;

        return $this;
    }
}

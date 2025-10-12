<?php

namespace App\Entity;

use App\Repository\CollectibleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Listing;

#[ORM\Entity(repositoryClass: CollectibleRepository::class)]
class Collectible
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $franchise = null;

    // ManyToOne relationship with Category, nullable for migration safety
    #[ORM\ManyToOne(inversedBy: 'collectibles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $category = null;

    // ManyToOne relationship with User, nullable for migration safety
    #[ORM\ManyToOne(inversedBy: 'collectibles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    /**
     * @var Collection<int, Listing>
     */
    #[ORM\OneToMany(targetEntity: Listing::class, mappedBy: 'collectible')]
    private Collection $listings;

    public function __construct()
    {
        $this->listings = new ArrayCollection();
    }

    // -------------------------
    // Getters and Setters
    // -------------------------

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

    /**
     * @return Collection<int, Listing>
     */
    public function getListings(): Collection
    {
        return $this->listings;
    }

    public function addListing(Listing $listing): static
    {
        if (!$this->listings->contains($listing)) {
            $this->listings->add($listing);
            $listing->setCollectible($this);
        }
        return $this;
    }

    public function removeListing(Listing $listing): static
    {
        if ($this->listings->removeElement($listing)) {
            if ($listing->getCollectible() === $this) {
                $listing->setCollectible(null);
            }
        }
        return $this;
    }

        public function getFranchise(): ?string
    {
        return $this->franchise;
    }

    public function setFranchise(?string $franchise): static
    {
        $this->franchise = $franchise;
        return $this;
    }
}

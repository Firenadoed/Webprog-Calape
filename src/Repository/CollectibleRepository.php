<?php

namespace App\Repository;

use App\Entity\Collectible;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Collectible>
 *
 * @method Collectible|null find($id, $lockMode = null, $lockVersion = null)
 * @method Collectible|null findOneBy(array $criteria, array $orderBy = null)
 * @method Collectible[]    findAll()
 * @method Collectible[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectibleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collectible::class);
    }

    /**
     * Finds collectibles based on search term and category.
     *
     * @param string|null $searchTerm
     * @param int|string|null $categoryId
     * @return Collectible[] Returns an array of Collectible objects
     */
    public function findByFilters(?string $searchTerm, $categoryId): array
    {
        $qb = $this->createQueryBuilder('c');

        // Apply search filter
        if ($searchTerm) {
            $qb->andWhere('c.name LIKE :searchTerm OR c.description LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // Apply category filter
        if ($categoryId && $categoryId !== '') { // Check if categoryId is not empty string
            $qb->andWhere('c.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        // Order by ID by default, or any other preference
        $qb->orderBy('c.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    // ... (your existing methods) ...
}
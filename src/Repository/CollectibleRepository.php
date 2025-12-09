<?php

namespace App\Repository;

use App\Entity\Collectible;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Collectible>
 */
class CollectibleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collectible::class);
    }

//    /**
//     * @return Collectible[] Returns an array of Collectible objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Collectible
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


// src/Repository/CollectibleRepository.php
public function findByUser($user): array
{
    // Check if $user is an ID (integer) or User entity
    if (is_numeric($user)) {
        return $this->createQueryBuilder('c')
            ->where('c.user = :userId')
            ->setParameter('userId', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    } else {
        // $user is a User entity
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

}

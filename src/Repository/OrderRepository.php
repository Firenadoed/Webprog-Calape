<?php
// src/Repository/OrderRepository.php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPendingOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('o.orderedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUserOrders($user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.buyer = :user')
            ->setParameter('user', $user)
            ->orderBy('o.orderedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countPendingOrders(): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();
    }
    // Add this method to your OrderRepository.php
public function findSellerOrders(User $user): array
{
    return $this->createQueryBuilder('o')
        ->join('o.listing', 'l')
        ->where('l.user = :user')
        ->setParameter('user', $user)
        ->orderBy('o.orderedAt', 'DESC')
        ->getQuery()
        ->getResult();
}
}
<?php

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

    public function findBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.transactionId LIKE :search')
            ->orWhere('o.customerName LIKE :search')
            ->orWhere('o.customerEmail LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Products;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Products>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Products::class);
    }

    /**
     * Search products with pagination.
     *
     * @param string $query
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function searchProducts(string $query, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('p')
            ->select('p.id, p.name, p.sku, p.price, p.stock') // only fields you need
            ->where('p.name LIKE :query')
            ->orWhere('p.sku LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult(); // returns array instead of entities
    }

    /**
     * Count products with low stock.
     *
     * @param int $threshold
     * @return int
     */
    public function countLowStock(int $threshold): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.stock < :threshold')
            ->andWhere('p.stock > 0')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get total inventory value.
     *
     * @return float
     */
    public function getTotalInventoryValue(): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.price * p.stock) as total')
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : 0.0;
    }
}

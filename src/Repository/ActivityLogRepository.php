<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Find logs with filters
     */
    public function findWithFilters(array $filters = [], int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        if (!empty($filters['username'])) {
            $qb->andWhere('a.username LIKE :username')
               ->setParameter('username', '%' . $filters['username'] . '%');
        }

        if (!empty($filters['action'])) {
            $qb->andWhere('a.action LIKE :action')
               ->setParameter('action', '%' . $filters['action'] . '%');
        }

        if (!empty($filters['role'])) {
            $qb->andWhere('a.role = :role')
               ->setParameter('role', $filters['role']);
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('a.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('a.createdAt <= :dateTo')
               ->setParameter('dateTo', $filters['dateTo']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get activity statistics
     */
    public function getStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = 'SELECT 
                    COUNT(*) as total_actions,
                    COUNT(DISTINCT username) as unique_users,
                    DATE(created_at) as action_date,
                    COUNT(*) as daily_count
                FROM activity_log
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY action_date
                ORDER BY action_date DESC';
        
        return $conn->executeQuery($sql)->fetchAllAssociative();
    }
}

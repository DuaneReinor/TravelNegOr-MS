<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use App\Entity\User;
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
     * Get recent activity logs with optional filtering
     */
    public function getRecentActivity(int $limit = 50, ?User $user = null, ?string $entityType = null): array
    {
        $qb = $this->createQueryBuilder('al')
            ->leftJoin('al.user', 'u')
            ->addSelect('u')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($user) {
            $qb->andWhere('al.user = :user')
               ->setParameter('user', $user);
        }

        if ($entityType) {
            $qb->andWhere('al.entityType = :entityType')
               ->setParameter('entityType', $entityType);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get activity logs for a specific entity
     */
    public function getEntityActivity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('al')
            ->leftJoin('al.user', 'u')
            ->addSelect('u')
            ->andWhere('al.entityType = :entityType')
            ->andWhere('al.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('al.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get activity logs by user
     */
    public function getUserActivity(User $user, int $limit = 100): array
    {
        return $this->createQueryBuilder('al')
            ->andWhere('al.user = :user')
            ->setParameter('user', $user)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(): array
    {
        $qb = $this->createQueryBuilder('al')
            ->select('al.action, al.entityType, COUNT(al.id) as count')
            ->groupBy('al.action, al.entityType')
            ->orderBy('count', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Clean old activity logs (older than specified days)
     */
    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = new \DateTimeImmutable();
        $cutoffDate = $cutoffDate->modify("-{$daysToKeep} days");

        return $this->createQueryBuilder('al')
            ->delete()
            ->andWhere('al.createdAt < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Search activity logs
     */
    public function searchLogs(string $searchTerm, int $limit = 50): array
    {
        return $this->createQueryBuilder('al')
            ->leftJoin('al.user', 'u')
            ->addSelect('u')
            ->andWhere('al.action LIKE :searchTerm OR al.entityType LIKE :searchTerm OR al.description LIKE :searchTerm OR al.entityName LIKE :searchTerm OR u.email LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
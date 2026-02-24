<?php

namespace App\Repository;

use App\Entity\UserBadge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBadge>
 */
class UserBadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBadge::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('ub')
            ->leftJoin('ub.badge', 'b')
            ->addSelect('b')
            ->where('ub.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ub.earnedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentBadges(int $userId, int $limit = 5): array
    {
        return $this->createQueryBuilder('ub')
            ->leftJoin('ub.badge', 'b')
            ->addSelect('b')
            ->where('ub.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ub.earnedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUserBadges(int $userId): int
    {
        return $this->createQueryBuilder('ub')
            ->select('COUNT(ub.id)')
            ->where('ub.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByUserAndBadge(int $userId, int $badgeId): ?UserBadge
    {
        return $this->createQueryBuilder('ub')
            ->where('ub.user = :userId')
            ->andWhere('ub.badge = :badgeId')
            ->setParameter('userId', $userId)
            ->setParameter('badgeId', $badgeId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

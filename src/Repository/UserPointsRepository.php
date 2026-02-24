<?php

namespace App\Repository;

use App\Entity\UserPoints;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPoints>
 */
class UserPointsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPoints::class);
    }

    public function findByUser(int $userId): ?UserPoints
    {
        try {
            return $this->createQueryBuilder('up')
                ->leftJoin('up.currentLevel', 'ul')
                ->addSelect('ul')
                ->where('up.user = :userId')
                ->setParameter('userId', $userId)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getLeaderboard(int $limit = 10): array
    {
        return $this->createQueryBuilder('up')
            ->leftJoin('up.user', 'u')
            ->leftJoin('up.currentLevel', 'ul')
            ->addSelect('u', 'ul')
            ->orderBy('up.totalPoints', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getUserRank(int $userId): int
    {
        try {
            $qb = $this->createQueryBuilder('up')
                ->select('COUNT(up.id) + 1')
                ->where('up.totalPoints > :userPoints')
                ->setParameter('userPoints', $this->getUserTotalPoints($userId))
                ->getQuery()
                ->getSingleScalarResult();

            return (int) $qb;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return 1; // If no users exist, rank is 1
        }
    }

    private function getUserTotalPoints(int $userId): int
    {
        try {
            $points = $this->createQueryBuilder('up')
                ->select('up.totalPoints')
                ->where('up.user = :userId')
                ->setParameter('userId', $userId)
                ->getQuery()
                ->getSingleScalarResult();

            return $points ?? 0;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return 0;
        }
    }

    public function updateRanks(): void
    {
        $this->createQueryBuilder('up')
            ->update()
            ->set('up.rankPosition', '(
                SELECT COUNT(up2.id) + 1 
                FROM App\Entity\UserPoints up2 
                WHERE up2.totalPoints > up.totalPoints
            )')
            ->getQuery()
            ->execute();
    }
}

<?php

namespace App\Repository;

use App\Entity\UserLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLevel>
 */
class UserLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLevel::class);
    }

    public function findByXpRange(int $xp): ?UserLevel
    {
        return $this->createQueryBuilder('ul')
            ->where('ul.minXp <= :xp')
            ->andWhere('ul.maxXp >= :xp')
            ->setParameter('xp', $xp)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('ul')
            ->orderBy('ul.levelOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextLevel(UserLevel $currentLevel): ?UserLevel
    {
        return $this->createQueryBuilder('ul')
            ->where('ul.levelOrder = :nextOrder')
            ->setParameter('nextOrder', $currentLevel->getLevelOrder() + 1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

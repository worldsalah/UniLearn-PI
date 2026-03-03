<?php

namespace App\Repository;

use App\Entity\Badge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Badge>
 */
class BadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Badge::class);
    }

    public function findActiveBadges(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('b.badgeOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.category = :category')
            ->andWhere('b.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('b.badgeOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?Badge
    {
        return $this->createQueryBuilder('b')
            ->where('b.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

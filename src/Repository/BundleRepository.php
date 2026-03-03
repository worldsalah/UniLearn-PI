<?php

namespace App\Repository;

use App\Entity\Bundle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bundle>
 */
class BundleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bundle::class);
    }

    /**
     * Find active bundles for a student
     */
    public function findActiveByStudent(string $studentId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.student = :student')
            ->andWhere('b.status = :active')
            ->setParameter('student', $studentId)
            ->setParameter('active', Bundle::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired but still active bundles
     */
    public function findExpiredActive(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.status = :active')
            ->andWhere('b.expiresAt < :now')
            ->setParameter('active', Bundle::STATUS_ACTIVE)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}

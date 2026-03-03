<?php

namespace App\Repository;

use App\Entity\Availability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Availability>
 */
class AvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Availability::class);
    }

    /**
     * Find active availabilities for a teacher
     */
    public function findActiveByTeacher(string $teacherProfileId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.teacher = :teacher')
            ->andWhere('a.isActive = true')
            ->setParameter('teacher', $teacherProfileId)
            ->orderBy('a.dayOfWeek', 'ASC')
            ->addOrderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by teacher and day
     */
    public function findByTeacherAndDay(string $teacherProfileId, int $dayOfWeek): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.teacher = :teacher')
            ->andWhere('a.dayOfWeek = :day')
            ->andWhere('a.isActive = true')
            ->setParameter('teacher', $teacherProfileId)
            ->setParameter('day', $dayOfWeek)
            ->getQuery()
            ->getResult();
    }
}

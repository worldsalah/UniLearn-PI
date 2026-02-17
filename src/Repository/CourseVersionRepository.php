<?php

namespace App\Repository;

use App\Entity\CourseVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseVersion>
 */
class CourseVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseVersion::class);
    }

    public function findByCourseOrderedByVersion($course): array
    {
        return $this->createQueryBuilder('cv')
            ->where('cv.course = :course')
            ->setParameter('course', $course)
            ->orderBy('cv.versionNumber', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestVersion($course): ?CourseVersion
    {
        return $this->createQueryBuilder('cv')
            ->where('cv.course = :course')
            ->setParameter('course', $course)
            ->orderBy('cv.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser($user): array
    {
        return $this->createQueryBuilder('cv')
            ->where('cv.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('cv.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByCourse($course): int
    {
        return $this->createQueryBuilder('cv')
            ->select('COUNT(cv.id)')
            ->where('cv.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

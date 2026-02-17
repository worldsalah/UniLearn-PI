<?php

namespace App\Repository;

use App\Entity\CourseAuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseAuditLog>
 */
class CourseAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseAuditLog::class);
    }

    public function findByCourseOrderedByDate($course): array
    {
        return $this->createQueryBuilder('cal')
            ->where('cal.course = :course')
            ->setParameter('course', $course)
            ->orderBy('cal.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser($user): array
    {
        return $this->createQueryBuilder('cal')
            ->where('cal.changedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('cal.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatusTransition(string $fromStatus, string $toStatus): array
    {
        return $this->createQueryBuilder('cal')
            ->where('cal.fromStatus = :fromStatus')
            ->andWhere('cal.toStatus = :toStatus')
            ->setParameter('fromStatus', $fromStatus)
            ->setParameter('toStatus', $toStatus)
            ->orderBy('cal.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByCourseAndStatus($course, string $status): int
    {
        return $this->createQueryBuilder('cal')
            ->select('COUNT(cal.id)')
            ->where('cal.course = :course')
            ->andWhere('cal.toStatus = :status')
            ->setParameter('course', $course)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 */
class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.course', 'c')
            ->leftJoin('e.user', 'u')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.enrolledAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndCourse(User $user, Course $course): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.course = :course')
            ->setParameter('user', $user)
            ->setParameter('course', $course)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByCourse(Course $course): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function updateProgress(Enrollment $enrollment, float $progress): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update(Enrollment::class, 'e')
            ->set('e.progress', ':progress')
            ->where('e.id = :id')
            ->setParameter('progress', $progress)
            ->setParameter('id', $enrollment->getId())
            ->getQuery()
            ->execute();
    }

    public function getEnrollmentsByMonth(): array
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id) as enrollmentCount', 'MONTH(e.enrolledAt) as month', 'YEAR(e.enrolledAt) as year')
            ->where('e.enrolledAt >= :startDate')
            ->setParameter('startDate', new \DateTime('-1 year'))
            ->groupBy('MONTH(e.enrolledAt)', 'YEAR(e.enrolledAt)')
            ->orderBy('YEAR(e.enrolledAt)', 'DESC')
            ->addOrderBy('MONTH(e.enrolledAt)', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getEnrollmentsByCourse(): array
    {
        return $this->createQueryBuilder('e')
            ->select('c.title as courseTitle', 'COUNT(e.id) as enrollmentCount')
            ->leftJoin('e.course', 'c')
            ->groupBy('c.id')
            ->orderBy('enrollmentCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function getRecentEnrollments(int $days = 30): array
    {
        $startDate = new \DateTime("-{$days} days");
        
        return $this->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->leftJoin('e.course', 'c')
            ->select('e', 'u', 'c')
            ->where('e.enrolledAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->orderBy('e.enrolledAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }
}

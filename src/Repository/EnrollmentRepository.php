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
}

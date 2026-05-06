<?php

namespace App\Repository;

use App\Entity\Certificate;
use App\Entity\User;
use App\Entity\QuizResult;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Certificate>
 */
class CertificateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Certificate::class);
    }

    /**
     * Find certificates by user
     */
    public function findByUser(User $user): array
    {
        try {
            return $this->createQueryBuilder('c')
                ->where('c.user = :user')
                ->setParameter('user', $user)
                ->orderBy('c.generatedAt', 'DESC')
                ->setMaxResults(50)
                ->getQuery()
                ->getResult();
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            // Some certificates reference non-existent courses, return empty array
            return [];
        }
    }

    /**
     * Find certificate by quiz result
     */
    public function findByQuizResult(QuizResult $quizResult): ?Certificate
    {
        return $this->createQueryBuilder('c')
            ->where('c.quizResult = :quizResult')
            ->setParameter('quizResult', $quizResult)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find certificates by course
     */
    public function findByCourse(Course $course): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.course = :course')
            ->setParameter('course', $course)
            ->orderBy('c.generatedAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find certificate by user and course
     */
    public function findOneByUserAndCourse(User $user, Course $course): ?Certificate
    {
        try {
            return $this->createQueryBuilder('c')
                ->where('c.user = :user')
                ->andWhere('c.course = :course')
                ->setParameter('user', $user)
                ->setParameter('course', $course)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            // Course referenced by certificate doesn't exist
            return null;
        }
    }

    /**
     * Count certificates by user
     */
    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find recently downloaded certificates
     */
    public function findRecentlyDownloaded(User $user, int $days = 30): array
    {
        $date = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.lastDownloadedAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('c.lastDownloadedAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
}

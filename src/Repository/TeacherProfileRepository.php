<?php

namespace App\Repository;

use App\Entity\TeacherProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeacherProfile>
 */
class TeacherProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeacherProfile::class);
    }

    /**
     * Find verified teachers by subject
     */
    public function findBySubject(string $subject): array
    {
        return $this->createQueryBuilder('tp')
            ->where('JSON_CONTAINS(tp.subjects, :subject) = 1')
            ->andWhere('tp.isVerified = true')
            ->setParameter('subject', json_encode($subject))
            ->getQuery()
            ->getResult();
    }

    /**
     * Find top rated teachers
     */
    public function findTopRated(int $limit = 10): array
    {
        return $this->createQueryBuilder('tp')
            ->where('tp.isVerified = true')
            ->andWhere('tp.reviewCount > 0')
            ->orderBy('tp.ratingAvg', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

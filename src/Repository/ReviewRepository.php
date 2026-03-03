<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Get rating statistics for a teacher
     */
    public function getTeacherRatingStats(string $teacherId): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as average, COUNT(r.id) as count')
            ->where('r.teacher = :teacher')
            ->setParameter('teacher', $teacherId)
            ->getQuery()
            ->getOneOrNullResult();
        
        return [
            'average' => $result !== null ? round((float) $result['average'], 2) : 0,
            'count' => $result !== null ? (int) $result['count'] : 0
        ];
    }

    /**
     * Find reviews by session
     */
    public function findBySession(string $sessionId): ?Review
    {
        return $this->createQueryBuilder('r')
            ->where('r.session = :session')
            ->setParameter('session', $sessionId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

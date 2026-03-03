<?php

namespace App\Repository;

use App\Entity\MeetingFeedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MeetingFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MeetingFeedback::class);
    }

    public function save(MeetingFeedback $feedback, bool $flush = true): void
    {
        $this->getEntityManager()->persist($feedback);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByBooking(int $bookingId): array
    {
        return $this->createQueryBuilder('mf')
            ->where('mf.booking = :bookingId')
            ->setParameter('bookingId', $bookingId)
            ->orderBy('mf.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('mf')
            ->where('mf.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('mf.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    public function hasUserSubmittedFeedback(int $bookingId, int $userId): bool
    {
        $count = $this->createQueryBuilder('mf')
            ->select('COUNT(mf.id)')
            ->where('mf.booking = :bookingId')
            ->andWhere('mf.user = :userId')
            ->setParameter('bookingId', $bookingId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function getAverageRatingsForBooking(int $bookingId): array
    {
        $result = $this->createQueryBuilder('mf')
            ->select(
                'AVG(mf.satisfactionRating) as avgSatisfaction',
                'AVG(mf.callQualityRating) as avgCallQuality',
                'AVG(mf.learningStyleRating) as avgLearningStyle',
                'COUNT(mf.id) as totalFeedbacks'
            )
            ->where('mf.booking = :bookingId')
            ->setParameter('bookingId', $bookingId)
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'satisfaction' => round($result['avgSatisfaction'] ?? 0, 1),
            'callQuality' => round($result['avgCallQuality'] ?? 0, 1),
            'learningStyle' => round($result['avgLearningStyle'] ?? 0, 1),
            'total' => (int) ($result['totalFeedbacks'] ?? 0),
        ];
    }
}

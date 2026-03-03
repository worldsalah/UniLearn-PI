<?php

namespace App\Repository;

use App\Entity\TutoringSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TutoringSession>
 */
class TutoringSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TutoringSession::class);
    }

    /**
     * Find sessions that need to be auto-completed
     */
    public function findScheduledButEnded(): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.booking', 'b')
            ->join('b.timeSlot', 'ts')
            ->where('s.status = :scheduled')
            ->andWhere('CONCAT(ts.date, \' \', ts.endTime) < :now')
            ->setParameter('scheduled', TutoringSession::STATUS_SCHEDULED)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}

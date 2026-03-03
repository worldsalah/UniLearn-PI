<?php

namespace App\Repository;

use App\Entity\TimeSlot;
use App\Entity\Availability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeSlot>
 */
class TimeSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSlot::class);
    }

    /**
     * Find with lock for booking (prevents race conditions)
     */
    public function findWithLock(string $id): ?TimeSlot
    {
        return $this->createQueryBuilder('ts')
            ->where('ts.id = :id')
            ->setParameter('id', $id)
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find available slots for a teacher in date range
     */
    public function findAvailableByTeacher(string $teacherProfileId, \DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('ts')
            ->join('ts.availability', 'a')
            ->where('a.teacher = :teacher')
            ->andWhere('ts.date >= :startDate')
            ->andWhere('ts.date <= :endDate')
            ->andWhere('ts.status = :status')
            ->setParameter('teacher', $teacherProfileId)
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->setParameter('status', TimeSlot::STATUS_AVAILABLE)
            ->orderBy('ts.date', 'ASC')
            ->addOrderBy('ts.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if availability has booked slots
     */
    public function hasBookedSlots(Availability $availability): bool
    {
        $count = $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->where('ts.availability = :availability')
            ->andWhere('ts.status = :booked')
            ->setParameter('availability', $availability)
            ->setParameter('booked', TimeSlot::STATUS_BOOKED)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $count > 0;
    }

    /**
     * Find old unused slots for cleanup
     */
    public function findOldUnusedSlots(\DateTime $cutoffDate): array
    {
        return $this->createQueryBuilder('ts')
            ->where('ts.date < :cutoff')
            ->andWhere('ts.status = :available')
            ->setParameter('cutoff', $cutoffDate->format('Y-m-d'))
            ->setParameter('available', TimeSlot::STATUS_AVAILABLE)
            ->getQuery()
            ->getResult();
    }
}

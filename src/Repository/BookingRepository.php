<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    //POST
    public function save(Booking $booking, bool $flush = true): void
    {
        $this->getEntityManager()->persist($booking);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //GET 
    public function findAllBookings(): array
    {
        return $this->createQueryBuilder('bookings')
            ->getQuery()
            ->getResult();
    }
}


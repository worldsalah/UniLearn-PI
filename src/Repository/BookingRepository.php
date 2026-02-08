<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Booking;
//repository : elle interagit avec la base de données pour faire les opérations comme post( ajout de booking) , get ( récupération des bookings de la base de données)  , put(modification d'un booking ) , delete  
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

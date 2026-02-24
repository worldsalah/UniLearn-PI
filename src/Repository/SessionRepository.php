<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    // POST
    public function save(Session $session, bool $flush = true): void
    {
        $this->getEntityManager()->persist($session);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // GET
    public function findAllSessions(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.instructor', 'i')
            ->leftJoin('s.category', 'c')
            ->addSelect('i', 'c')
            ->orderBy('s.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findSessionsByInstructor(int $instructorId): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.instructor', 'i')
            ->leftJoin('s.category', 'c')
            ->addSelect('i', 'c')
            ->where('i.id = :instructorId')
            ->setParameter('instructorId', $instructorId)
            ->orderBy('s.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findSessionsWithInstructorInfo(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.instructor', 'i')
            ->leftJoin('s.category', 'c')
            ->addSelect('i', 'c')
            ->orderBy('s.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

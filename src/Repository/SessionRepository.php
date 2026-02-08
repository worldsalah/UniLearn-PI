<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Session;


//repository : elle interagit avec la base de données pour faire les opérations comme post( ajout de Session) , get ( récupération des Sessions de la base de données)  , put(modification d'un Session ) , delete  
class SessionRepository extends ServiceEntityRepository
{

     public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    //POST
    public function save(Session $Session, bool $flush = true): void
    {
        $this->getEntityManager()->persist($Session);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //GET 
      public function findAllSessions(): array
    {
        return $this->createQueryBuilder('Sessions')
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findBySearchAndSort(string $search, string $sortBy, string $sortOrder): array
    {
        $qb = $this->createQueryBuilder('u');

        if (!empty($search)) {
            $qb->andWhere('u.fullName LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Handle sorting
        $allowedSortBy = ['id', 'fullName', 'email', 'createdAt'];
        if (in_array($sortBy, $allowedSortBy)) {
            $qb->orderBy('u.' . $sortBy, $sortOrder);
        } elseif ($sortBy === 'role') {
            $qb->leftJoin('u.role', 'r')
               ->orderBy('r.name', $sortOrder);
        } else {
            // Default sort
            $qb->orderBy('u.id', 'asc');
        }

        return $qb->getQuery()->getResult();
    }
}

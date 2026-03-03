<?php

namespace App\Search;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * User finder using database queries
 */
class UserFinder
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Find users by name or email
     */
    public function find(string $query, int $limit = 10): array
    {
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.fullName LIKE :query OR u.email LIKE :query')
            ->setParameter('query', $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with pagination
     */
    public function findPaginated(string $query, int $page = 1, int $limit = 10): array
    {
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.fullName LIKE :query OR u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

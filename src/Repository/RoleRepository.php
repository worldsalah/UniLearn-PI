<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    private static array $roleCache = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * Find role by name with caching (static table optimization).
     */
    public function findOneByName(string $name): ?Role
    {
        if (isset(self::$roleCache[$name])) {
            return self::$roleCache[$name];
        }

        $role = $this->createQueryBuilder('r')
            ->where('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->useResultCache(true, 3600, 'role_' . $name)
            ->getOneOrNullResult();

        if ($role !== null) {
            self::$roleCache[$name] = $role;
        }

        return $role;
    }

    /**
     * Find role by ID with caching.
     */
    public function findCached(int $id): ?Role
    {
        if (isset(self::$roleCache[$id])) {
            return self::$roleCache[$id];
        }

        $role = $this->find($id);

        if ($role !== null) {
            self::$roleCache[$id] = $role;
            self::$roleCache[$role->getName()] = $role;
        }

        return $role;
    }

    /**
     * Get all roles with caching.
     */
    public function findAllCached(): array
    {
        return $this->createQueryBuilder('r')
            ->getQuery()
            ->useResultCache(true, 3600, 'all_roles')
            ->getResult();
    }
}

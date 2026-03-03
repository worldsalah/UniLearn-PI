<?php

namespace App\Repository;

use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Permission>
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    /**
     * Find permissions by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->setParameter('category', $category)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all permissions grouped by category
     */
    public function findAllGroupedByCategory(): array
    {
        $permissions = $this->findAll();
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $category = $permission->getCategory() ?? 'Uncategorized';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }
        
        return $grouped;
    }

    /**
     * Get all unique categories
     */
    public function findAllCategories(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->getQuery()
            ->getScalarResult();
        
        return array_column($result, 'category');
    }
}

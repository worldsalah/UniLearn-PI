<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findActiveCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->setParameter('isActive', 1)  // Use integer 1 instead of boolean true
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCategoriesWithCourseCount(): array
    {
        // Use native SQL to avoid Doctrine issues
        $sql = "
            SELECT c.id, c.name, c.description, c.icon, c.color, c.is_active, c.created_at, c.slug,
                   COUNT(co.id) as courseCount
            FROM category c
            LEFT JOIN course co ON c.id = co.category_id
            WHERE c.is_active = 1
            GROUP BY c.id, c.name, c.description, c.icon, c.color, c.is_active, c.created_at, c.slug
            ORDER BY c.name ASC
        ";
        
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        
        $categories = [];
        while ($row = $result->fetchAssociative()) {
            // Create a simple array with all category data
            $categoryData = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'icon' => $row['icon'],
                'color' => $row['color'],
                'isActive' => (bool)$row['is_active'],
                'createdAt' => new \DateTimeImmutable($row['created_at']),
                'slug' => $row['slug'],
                'courseCount' => (int) $row['courseCount']
            ];
            
            $categories[] = [
                0 => (object) $categoryData, // Convert to object for template compatibility
                'courseCount' => (int) $row['courseCount']
            ];
        }
        
        return $categories;
    }
}

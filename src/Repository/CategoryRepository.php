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
        // First get all active categories
        $categories = $this->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Then get course counts for each category
        foreach ($categories as $category) {
            $count = $this->getEntityManager()->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from('App\Entity\Course', 'c')
                ->where('c.category = :category')
                ->andWhere('c.status = :status')
                ->setParameter('category', $category->getId())
                ->setParameter('status', 'live')
                ->getQuery()
                ->getSingleScalarResult();

            // Add course count as a dynamic property
            $category->courseCount = (int) $count;
        }

        return $categories;
    }
}

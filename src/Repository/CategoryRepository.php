<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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
            ->setParameter('isActive', 1)
            ->orderBy('c.name', 'ASC')
            ->setMaxResults(99)
            ->getQuery()
            ->useResultCache(false)
            ->getArrayResult();
    }

    /**
     * Find categories with course count in single query (fixes N+1).
     */
    public function findCategoriesWithCourseCount(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(co.id) as courseCount')
            ->leftJoin('c.courses', 'co', 'WITH', 'co.status = :status')
            ->where('c.isActive = :isActive')
            ->setParameter('status', 'live')
            ->setParameter('isActive', true)
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->useResultCache(false)
            ->getResult();
    }

    /**
     * Find active categories ordered by name with caching.
     */
    public function findActiveOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('c.name', 'ASC')
            ->setMaxResults(99)
            ->getQuery()
            ->useResultCache(false)
            ->getArrayResult();
    }

    /**
     * Find active categories that have live courses (for homepage).
     */
    public function findCategoriesWithCourses(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id, c.name')
            ->join('c.courses', 'co')
            ->where('c.isActive = :isActive')
            ->andWhere('co.status = :status')
            ->setParameter('isActive', true)
            ->setParameter('status', 'live')
            ->groupBy('c.id')
            ->orderBy('COUNT(co.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->useResultCache(false)
            ->getArrayResult();
    }
}

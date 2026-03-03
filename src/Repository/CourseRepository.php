<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * Find courses by user with eager loading (fixes N+1).
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.chapters', 'ch')->addSelect('ch')
            ->leftJoin('ch.lessons', 'l')->addSelect('l')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active courses by category name (NO eager loading with LIMIT).
     */
    public function findByCategoryName(string $categoryName, int $limit = 4): array
    {
        // Get courses without collections (safe with LIMIT)
        $courses = $this->createQueryBuilder('c')
            ->join('c.category', 'cat')
            ->where('cat.name = :categoryName')
            ->andWhere('c.status = :status')
            ->setParameter('categoryName', $categoryName)
            ->setParameter('status', 'live')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->useResultCache(false)
            ->getResult();

        // Load chapters/lessons separately to avoid LIMIT issues
        $this->loadChaptersAndLessons($courses);

        return $courses;
    }

    /**
     * Find active courses by category ID (NO eager loading with LIMIT).
     */
    public function findByCategoryId(int $categoryId, int $limit = 4): array
    {
        $courses = $this->createQueryBuilder('c')
            ->join('c.category', 'cat')
            ->where('cat.id = :categoryId')
            ->andWhere('c.status = :status')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('status', 'live')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->useResultCache(false)
            ->getResult();

        $this->loadChaptersAndLessons($courses);

        return $courses;
    }

    /**
     * Find courses by category ID (any status, NO eager loading with LIMIT).
     */
    public function findByCategoryIdAnyStatus(int $categoryId, int $limit = 4): array
    {
        $courses = $this->createQueryBuilder('c')
            ->join('c.category', 'cat')
            ->where('cat.id = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $this->loadChaptersAndLessons($courses);

        return $courses;
    }

    /**
     * Find popular active courses (NO eager loading with LIMIT).
     */
    public function findPopular(int $limit = 4): array
    {
        $courses = $this->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->where('c.status = :status')
            ->setParameter('status', 'live')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->useResultCache(false)
            ->getResult();

        $this->loadChaptersAndLessons($courses);

        return $courses;
    }

    /**
     * Find all courses (NO eager loading with LIMIT).
     */
    public function findAllOrdered(int $limit = 5): array
    {
        $courses = $this->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $this->loadChaptersAndLessons($courses);

        return $courses;
    }

    /**
     * Search courses (NO eager loading with LIMIT).
     */
    public function searchCourses(string $query, ?string $level = null, int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->where('c.status = :status')
            ->andWhere('c.title LIKE :query OR c.description LIKE :query')
            ->setParameter('status', 'live')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($level !== null) {
            $qb->andWhere('c.level = :level')
               ->setParameter('level', $level);
        }

        $results = $qb->getQuery()->getResult();

        $this->loadChaptersAndLessons($results);

        return [
            'courses' => $results,
            'pagination' => [
                'currentPage' => $page,
                'itemsPerPage' => $limit,
                'totalItems' => count($results),
                'totalPages' => 1
            ]
        ];
    }

    /**
     * Find all courses with pagination (using Paginator for proper handling).
     */
    public function findAllWithEagerLoading(int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery(), true);
        
        $courses = iterator_to_array($paginator);
        $this->loadChaptersAndLessons($courses);

        return $courses;
    }

    /**
     * Find one course with full eager loading (no LIMIT, safe).
     */
    public function findOneWithEagerLoading(int $id): ?Course
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.chapters', 'ch')->addSelect('ch')
            ->leftJoin('ch.lessons', 'l')->addSelect('l')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Batch load chapters and lessons for multiple courses (fixes N+1 without LIMIT issues).
     */
    private function loadChaptersAndLessons(array $courses): void
    {
        if (empty($courses)) {
            return;
        }

        $courseIds = array_map(fn($c) => $c->getId(), $courses);

        // Batch load chapters with lessons
        $this->getEntityManager()->createQueryBuilder()
            ->select('ch', 'l')
            ->from('App\Entity\Chapter', 'ch')
            ->leftJoin('ch.lessons', 'l')
            ->where('ch.course IN (:courseIds)')
            ->setParameter('courseIds', $courseIds)
            ->orderBy('ch.sortOrder', 'ASC')
            ->addOrderBy('l.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find courses for multiple categories in single query (fixes N+1 in HomeController).
     * Uses multi-step hydration to avoid O(n^2) cartesian product.
     * Returns array grouped by categoryId => courses[]
     */
    public function findByCategoryIds(array $categoryIds, ?string $status = null, int $limitPerCategory = 4): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        // Step 1: Load courses with category only (no collections)
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->where('c.category IN (:categoryIds)')
            ->setParameter('categoryIds', $categoryIds)
            ->orderBy('c.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        $allCourses = $qb->getQuery()
            ->useResultCache(false)
            ->getResult();

        if (empty($allCourses)) {
            return [];
        }

        // Step 2: Batch load chapters for all courses (avoids N+1, no cartesian product)
        $courseIds = array_map(fn($c) => $c->getId(), $allCourses);
        $this->getEntityManager()->createQueryBuilder()
            ->select('PARTIAL c.{id}', 'ch')
            ->from('App\Entity\Course', 'c')
            ->leftJoin('c.chapters', 'ch')
            ->where('c.id IN (:courseIds)')
            ->setParameter('courseIds', $courseIds)
            ->orderBy('ch.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();

        // Step 3: Batch load lessons for all chapters (avoids N+1, no cartesian product)
        $this->getEntityManager()->createQueryBuilder()
            ->select('PARTIAL ch.{id}', 'l')
            ->from('App\Entity\Chapter', 'ch')
            ->leftJoin('ch.lessons', 'l')
            ->where('ch.course IN (:courseIds)')
            ->setParameter('courseIds', $courseIds)
            ->orderBy('l.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();

        // Group by category and limit
        $grouped = [];
        foreach ($allCourses as $course) {
            $catId = $course->getCategory()?->getId();
            if ($catId === null) {
                continue;
            }
            if (!isset($grouped[$catId])) {
                $grouped[$catId] = [];
            }
            if (count($grouped[$catId]) < $limitPerCategory) {
                $grouped[$catId][] = $course;
            }
        }

        return $grouped;
    }
}

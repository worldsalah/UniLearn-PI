<?php

namespace App\Repository;

use App\Entity\Chapter;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chapter>
 *
 * @method Chapter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Chapter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Chapter[]    findAll()
 * @method Chapter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChapterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chapter::class);
    }

    /**
     * Find chapters by course with eager loading (fixes N+1).
     */
    public function findByCourseWithLessons(Course $course): array
    {
        return $this->createQueryBuilder('ch')
            ->leftJoin('ch.lessons', 'l')->addSelect('l')
            ->where('ch.course = :course')
            ->setParameter('course', $course)
            ->orderBy('ch.sortOrder', 'ASC')
            ->addOrderBy('l.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

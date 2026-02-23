<?php

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 *
 * @method Lesson|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lesson|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lesson[]    findAll()
 * @method Lesson[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    public function findByCourse(Course $course): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.chapter', 'c')
            ->where('c.course = :course')
            ->setParameter('course', $course)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('l.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFirstLessonByCourse(Course $course): ?Lesson
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.chapter', 'c')
            ->where('c.course = :course')
            ->setParameter('course', $course)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('l.sortOrder', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

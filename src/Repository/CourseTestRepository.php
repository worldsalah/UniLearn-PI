<?php

namespace App\Repository;

use App\Entity\CourseTest;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseTest>
 */
class CourseTestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseTest::class);
    }

    public function findByCourse(Course $course): ?CourseTest
    {
        return $this->createQueryBuilder('ct')
            ->where('ct.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getOneOrNull();
    }

    public function findWithQuestions(Course $course): ?CourseTest
    {
        return $this->createQueryBuilder('ct')
            ->leftJoin('ct.questions', 'q')
            ->addSelect('ct', 'q')
            ->where('ct.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getOneOrNull();
    }
}

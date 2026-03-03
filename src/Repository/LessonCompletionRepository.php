<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\LessonCompletion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LessonCompletion>
 *
 * @method LessonCompletion|null find($id, $lockMode = null, $lockVersion = null)
 * @method LessonCompletion|null findOneBy(array $criteria, array $orderBy = null)
 * @method LessonCompletion[]    findAll()
 * @method LessonCompletion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonCompletion::class);
    }

    public function save(LessonCompletion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LessonCompletion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByUserAndLesson(User $user, Lesson $lesson): ?LessonCompletion
    {
        return $this->createQueryBuilder('lc')
            ->where('lc.user = :user')
            ->andWhere('lc.lesson = :lesson')
            ->setParameter('user', $user)
            ->setParameter('lesson', $lesson)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserAndCourse(User $user, Course $course): array
    {
        return $this->createQueryBuilder('lc')
            ->where('lc.user = :user')
            ->andWhere('lc.course = :course')
            ->setParameter('user', $user)
            ->setParameter('course', $course)
            ->orderBy('lc.completedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function isLessonCompleted(User $user, Lesson $lesson): bool
    {
        return $this->findOneByUserAndLesson($user, $lesson) !== null;
    }
}

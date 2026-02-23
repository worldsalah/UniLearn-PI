<?php

namespace App\Repository;

use App\Entity\LearningRoadmap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LearningRoadmap>
 */
class LearningRoadmapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearningRoadmap::class);
    }

    /**
     * Find all roadmaps for a specific user
     */
    public function findByUser($user, $limit = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('r.createdAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the most recent roadmap for a user
     */
    public function findMostRecentByUser($user): ?LearningRoadmap
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find roadmaps by learning goal
     */
    public function findByLearningGoal(string $goal, $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.learningGoal LIKE :goal')
            ->andWhere('r.isActive = :active')
            ->setParameter('goal', '%' . $goal . '%')
            ->setParameter('active', true)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics for a user's roadmaps
     */
    public function getUserStats($user): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) as total_roadmaps')
            ->addSelect('COUNT(CASE WHEN r.createdAt >= :recent THEN 1 END) as recent_roadmaps')
            ->addSelect('COUNT(CASE WHEN r.skillLevel = :beginner THEN 1 END) as beginner_roadmaps')
            ->addSelect('COUNT(CASE WHEN r.skillLevel = :intermediate THEN 1 END) as intermediate_roadmaps')
            ->addSelect('COUNT(CASE WHEN r.skillLevel = :advanced THEN 1 END) as advanced_roadmaps')
            ->where('r.user = :user')
            ->andWhere('r.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->setParameter('recent', (new \DateTime())->modify('-7 days'))
            ->setParameter('beginner', 'beginner')
            ->setParameter('intermediate', 'intermediate')
            ->setParameter('advanced', 'advanced')
            ->getQuery()
            ->getSingleResult();

        return $qb;
    }

    /**
     * Find inactive roadmaps for cleanup
     */
    public function findInactiveRoadmaps(int $daysOld = 30): array
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$daysOld} days");

        return $this->createQueryBuilder('r')
            ->where('r.isActive = :active')
            ->andWhere('r.createdAt < :cutoff')
            ->setParameter('active', false)
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Soft delete a roadmap (set inactive)
     */
    public function softDelete(LearningRoadmap $roadmap): void
    {
        $roadmap->setIsActive(false);
        $this->getEntityManager()->flush();
    }
}

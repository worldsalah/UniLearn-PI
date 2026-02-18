<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Job;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function findByJob(Job $job): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.job = :job')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('job', $job)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFreelancer(User $freelancer): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.freelancer = :freelancer')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('freelancer', $freelancer)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByJobAndFreelancer(Job $job, User $freelancer): ?Application
    {
        return $this->createQueryBuilder('a')
            ->where('a.job = :job')
            ->andWhere('a.freelancer = :freelancer')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('job', $job)
            ->setParameter('freelancer', $freelancer)
            ->getQuery()
            ->getOneOrNull();
    }

    public function countByJob(Job $job): int
    {
        return (int)$this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.job = :job')
            ->setParameter('job', $job)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('status', $status)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingApplications(): array
    {
        return $this->findByStatus('pending');
    }

    public function updateStatus(Application $application, string $status): void
    {
        $application->setStatus($status);
        $application->setUpdatedAt(new \DateTimeImmutable());
        $this->getEntityManager()->flush();
    }
}

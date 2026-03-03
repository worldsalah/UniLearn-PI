<?php

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    /**
     * Find all quizzes for courses owned by a specific user (teacher)
     */
    public function findByUser(int $userId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT q.id, q.title, q.created_at as createdAt
            FROM quiz q
            LEFT JOIN course c ON q.course_id = c.id
            WHERE c.user_id = :userId OR q.course_id IS NULL
            ORDER BY q.created_at DESC
        ';
        
        $resultSet = $conn->executeQuery($sql, ['userId' => $userId]);
        return $resultSet->fetchAllAssociative();
    }
}

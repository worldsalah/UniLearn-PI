<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FOS\ElasticaBundle\Manager\RepositoryManagerInterface;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    private RepositoryManagerInterface $repositoryManager;

    public function __construct(ManagerRegistry $registry, RepositoryManagerInterface $repositoryManager)
    {
        parent::__construct($registry, Course::class);
        $this->repositoryManager = $repositoryManager;
    }

    /**
     * Find courses by user.
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search courses using Elasticsearch.
     */
    public function searchCourses(string $query, ?string $level = null, int $page = 1, int $limit = 10): array
    {
        $finder = $this->repositoryManager->getFinder(Course::class);
        
        $searchQuery = [
            'bool' => [
                'must' => [
                    [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['title^2', 'description'],
                            'type' => 'best_fields',
                            'fuzziness' => 'AUTO'
                        ]
                    ]
                ],
                'filter' => [
                    [
                        'term' => [
                            'status.keyword' => 'active'
                        ]
                    ]
                ]
            ]
        ];

        if ($level) {
            $searchQuery['bool']['filter'][] = [
                'term' => [
                    'level.keyword' => $level
                ]
            ];
        }

        $results = $finder->find($searchQuery, $limit);

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
}

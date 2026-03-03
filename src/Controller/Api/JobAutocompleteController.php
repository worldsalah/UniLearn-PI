<?php

namespace App\Controller\Api;

use App\Entity\Job;
use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class JobAutocompleteController extends AbstractController
{
    private JobRepository $jobRepository;

    public function __construct(JobRepository $jobRepository)
    {
        $this->jobRepository = $jobRepository;
    }

    /**
     * Autocomplete search for jobs (database fallback)
     */
    #[Route('/autocomplete/jobs', name: 'api_autocomplete_jobs', methods: ['GET'])]
    public function autocompleteJobs(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen((string) $query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        try {
            // Database fallback search
            $jobs = $this->jobRepository->createQueryBuilder('j')
                ->where('j.title LIKE :query')
                ->setParameter('query', $query . '%')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $suggestions = [];
            foreach ($jobs as $job) {
                $suggestions[] = [
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'description' => substr($job->getDescription() ?? '', 0, 100) . '...',
                    'url' => $this->generateUrl('app_job_show', ['id' => $job->getId()])
                ];
            }

            return new JsonResponse([
                'success' => true,
                'suggestions' => $suggestions
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'suggestions' => []
            ], 500);
        }
    }
}

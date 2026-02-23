<?php

namespace App\Controller\Api;

use App\Entity\Job;
use FOS\ElasticaBundle\Finder\TransformedFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class JobAutocompleteController extends AbstractController
{
    private TransformedFinder $finder;

    public function __construct(
        #[Target('jobs.finder')]
        TransformedFinder $finder
    ) {
        $this->finder = $finder;
    }

    /**
     * Autocomplete search for jobs
     */
    #[Route('/autocomplete/jobs', name: 'api_autocomplete_jobs', methods: ['GET'])]
    public function autocompleteJobs(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen($query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        try {
            // Use prefix query with proper Elasticsearch syntax
            $searchQuery = [
                'query' => [
                    'prefix' => [
                        'title' => $query
                    ]
                ]
            ];
            $results = $this->finder->find($searchQuery, $limit);

            $suggestions = [];
            foreach ($results as $job) {
                $suggestions[] = [
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'description' => substr($job->getDescription(), 0, 100) . '...',
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

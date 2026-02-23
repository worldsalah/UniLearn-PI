<?php

namespace App\Controller\Api;

use App\Entity\User;
use FOS\ElasticaBundle\Finder\TransformedFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class UserAutocompleteController extends AbstractController
{
    private TransformedFinder $finder;

    public function __construct(
        #[Target('users.finder')]
        TransformedFinder $finder
    ) {
        $this->finder = $finder;
    }

    /**
     * Autocomplete search for users
     */
    #[Route('/autocomplete/users', name: 'api_autocomplete_users', methods: ['GET'])]
    public function autocompleteUsers(Request $request): JsonResponse
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
                        'fullName' => $query,
                        'email' => $query
                    ]
                ]
            ];
            $results = $this->finder->find($searchQuery, $limit);

            $suggestions = [];
            foreach ($results as $user) {
                $suggestions[] = [
                    'id' => $user->getId(),
                    'title' => $user->getFullName(),
                    'description' => $user->getEmail(),
                    'url' => '#'
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

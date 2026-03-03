<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class UserAutocompleteController extends AbstractController
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Autocomplete search for users (database-based)
     */
    #[Route('/autocomplete/users', name: 'api_autocomplete_users', methods: ['GET'])]
    public function autocompleteUsers(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen((string) $query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        try {
            // Database search
            $users = $this->userRepository->createQueryBuilder('u')
                ->where('u.fullName LIKE :query OR u.email LIKE :query')
                ->setParameter('query', $query . '%')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $suggestions = [];
            foreach ($users as $user) {
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

<?php

namespace App\Controller;

use App\Repository\QuizRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/quizzes', name: 'quizzes', methods: ['GET'])]
    public function quizzes(QuizRepository $quizRepository): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            if ($user === null) {
                return $this->json(['error' => 'Unauthorized', 'message' => 'User not logged in'], 401);
            }

            $userId = $user->getId();
            
            if (!$userId) {
                return $this->json(['error' => 'Unauthorized', 'message' => 'User ID not found'], 401);
            }

            $quizzes = $quizRepository->findByUser($userId);

            // Repository returns array results from raw SQL
            $data = [];
            foreach ($quizzes as $quiz) {
                $createdAt = $quiz['createdAt'] ?? null;
                $data[] = [
                    'id' => (int) $quiz['id'],
                    'title' => $quiz['title'] ?: 'Untitled',
                    'questionCount' => 0,
                    'createdAt' => $createdAt ? (is_string($createdAt) ? $createdAt : $createdAt->format('Y-m-d H:i')) : null,
                    'courseTitle' => null,
                ];
            }

            return $this->json($data);
        } catch (\Doctrine\ORM\Query\QueryException $e) {
            return $this->json([
                'error' => 'Query error',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}

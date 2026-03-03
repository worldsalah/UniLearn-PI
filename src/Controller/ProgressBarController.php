<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\GamificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/progress')]
class ProgressBarController extends AbstractController
{
    private GamificationService $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    #[Route('/demo', name: 'app_progress_demo')]
    public function demo(): Response
    {
        $user = $this->getUser();
        
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        // Get current stats
        $stats = $this->gamificationService->getGamificationStats($user);
        $progress = $this->gamificationService->getProgressToNextLevel($user);

        // Sample progress data for demonstration
        $sampleProgress = [
            [
                'title' => 'Course Progress',
                'current' => 12,
                'total' => 20,
                'color' => 'primary',
                'animated' => true
            ],
            [
                'title' => 'Quiz Average Score',
                'current' => 85,
                'total' => 100,
                'color' => 'success',
                'animated' => true
            ],
            [
                'title' => 'Assignment Completion',
                'current' => 8,
                'total' => 10,
                'color' => 'warning',
                'animated' => true
            ],
            [
                'title' => 'Forum Participation',
                'current' => 15,
                'total' => 50,
                'color' => 'info',
                'animated' => true
            ]
        ];

        // Step progress data
        $courseSteps = [
            ['title' => 'Getting Started', 'completed' => true],
            ['title' => 'Basic Concepts', 'completed' => true],
            ['title' => 'Advanced Topics', 'completed' => false, 'active' => true],
            ['title' => 'Final Project', 'completed' => false]
        ];

        // Skill levels
        $skills = [
            ['name' => 'JavaScript', 'level' => 4, 'maxLevel' => 5],
            ['name' => 'PHP', 'level' => 3, 'maxLevel' => 5],
            ['name' => 'CSS', 'level' => 5, 'maxLevel' => 5],
            ['name' => 'React', 'level' => 2, 'maxLevel' => 5]
        ];

        return $this->render('progress/demo.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'progress' => $progress,
            'sampleProgress' => $sampleProgress,
            'courseSteps' => $courseSteps,
            'skills' => $skills,
            'currentStep' => 2
        ]);
    }

    #[Route('/api/update-demo', name: 'app_progress_api_update_demo', methods: ['POST'])]
    public function updateDemo(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Simulate dynamic updates
        $updates = [];
        
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'course':
                    $updates = [
                        'current' => rand(0, 20),
                        'total' => 20
                    ];
                    break;
                case 'quiz':
                    $updates = [
                        'current' => rand(0, 100),
                        'total' => 100
                    ];
                    break;
                case 'assignment':
                    $updates = [
                        'current' => rand(0, 10),
                        'total' => 10
                    ];
                    break;
                case 'forum':
                    $updates = [
                        'current' => rand(0, 50),
                        'total' => 50
                    ];
                    break;
            }
        }
        
        return new JsonResponse([
            'success' => true,
            'updates' => $updates
        ]);
    }

    #[Route('/api/real-time', name: 'app_progress_api_real_time')]
    public function realTimeProgress(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $stats = $this->gamificationService->getGamificationStats($user);
        $progress = $this->gamificationService->getProgressToNextLevel($user);

        return new JsonResponse([
            'total_points' => $stats['total_points'],
            'current_level_points' => $progress['current_points'],
            'progress' => $progress['progress'],
            'points_to_next' => $progress['points_to_next'],
            'current_level' => $stats['current_level']->getName(),
            'next_level' => $progress['next_level'],
            'rank' => $stats['rank'],
            'badges_count' => $stats['badges_count']
        ]);
    }

    #[Route('/api/simulate-progress', name: 'app_progress_api_simulate', methods: ['POST'])]
    public function simulateProgress(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Simulate earning XP
        $pointsToAdd = $data['points'] ?? 10;
        
        $user = $this->getUser();
        
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        try {
            $this->gamificationService->addPoints($user, $pointsToAdd, 'Simulated progress update');
            
            $stats = $this->gamificationService->getGamificationStats($user);
            $progress = $this->gamificationService->getProgressToNextLevel($user);

            return new JsonResponse([
                'success' => true,
                'points_added' => $pointsToAdd,
                'new_stats' => [
                    'total_points' => $stats['total_points'],
                    'current_level_points' => $progress['current_points'],
                    'progress' => $progress['progress'],
                    'points_to_next' => $progress['points_to_next'],
                    'current_level' => $stats['current_level']->getName(),
                    'next_level' => $progress['next_level'],
                    'rank' => $stats['rank'],
                    'badges_count' => $stats['badges_count']
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update progress: ' . $e->getMessage()], 500);
        }
    }
}

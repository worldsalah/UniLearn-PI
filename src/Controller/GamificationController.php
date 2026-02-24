<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\GamificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gamification')]
class GamificationController extends AbstractController
{
    private GamificationService $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    #[Route('/profile', name: 'app_gamification_profile')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->gamificationService->initializeUserGamification($user);
        
        $stats = $this->gamificationService->getGamificationStats($user);
        $badges = $this->gamificationService->getUserBadges($user);
        $leaderboard = $this->gamificationService->getLeaderboard(10);
        $progress = $this->gamificationService->getProgressToNextLevel($user);

        return $this->render('gamification/profile.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'badges' => $badges,
            'leaderboard' => $leaderboard,
            'progress' => $progress
        ]);
    }

    #[Route('/leaderboard', name: 'app_gamification_leaderboard')]
    public function leaderboard(Request $request): Response
    {
        $limit = $request->query->getInt('limit', 20);
        $leaderboard = $this->gamificationService->getLeaderboard($limit);

        return $this->render('gamification/leaderboard.html.twig', [
            'leaderboard' => $leaderboard,
            'current_rank' => $this->getUser() ? $this->gamificationService->getUserRank($this->getUser()) : null
        ]);
    }

    #[Route('/badges', name: 'app_gamification_badges')]
    public function badges(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $badges = $this->gamificationService->getUserBadges($user);
        $stats = $this->gamificationService->getGamificationStats($user);

        return $this->render('gamification/badges.html.twig', [
            'user' => $user,
            'badges' => $badges,
            'stats' => $stats
        ]);
    }

    #[Route('/api/stats', name: 'app_gamification_api_stats')]
    public function apiStats(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $stats = $this->gamificationService->getGamificationStats($user);
        $progress = $this->gamificationService->getProgressToNextLevel($user);

        return new JsonResponse([
            'stats' => $stats,
            'progress' => $progress
        ]);
    }

    #[Route('/api/add-points', name: 'app_gamification_api_add_points')]
    public function addPoints(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Handle both JSON and form data
        $data = json_decode($request->getContent(), true) ?? [];
        $points = $data['points'] ?? $request->request->getInt('points', 0);
        $reason = $data['reason'] ?? $request->request->getString('reason', '');

        // Debug logging
        error_log('Gamification API - User ID: ' . $user->getId());
        error_log('Gamification API - Points: ' . $points);
        error_log('Gamification API - Reason: ' . $reason);
        error_log('Gamification API - Request content: ' . $request->getContent());

        if ($points <= 0) {
            return new JsonResponse(['error' => 'Invalid points amount: ' . $points], 400);
        }

        try {
            $this->gamificationService->addPoints($user, $points, $reason);

            return new JsonResponse([
                'success' => true,
                'message' => "Added {$points} points successfully",
                'new_stats' => $this->gamificationService->getGamificationStats($user)
            ]);
        } catch (\Exception $e) {
            error_log('Gamification API Error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/leaderboard', name: 'app_gamification_api_leaderboard')]
    public function apiLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 10);
        $leaderboard = $this->gamificationService->getLeaderboard($limit);

        $data = [];
        foreach ($leaderboard as $entry) {
            $data[] = [
                'rank' => $entry->getRankPosition(),
                'user' => [
                    'id' => $entry->getUser()->getId(),
                    'name' => $entry->getUser()->getFullName(),
                    'profile_image' => $entry->getUser()->getProfileImage()
                ],
                'total_points' => $entry->getTotalPoints(),
                'level' => $entry->getCurrentLevel() ? [
                    'name' => $entry->getCurrentLevel()->getName(),
                    'color' => $entry->getCurrentLevel()->getColor(),
                    'icon' => $entry->getCurrentLevel()->getIcon()
                ] : null
            ];
        }

        return new JsonResponse($data);
    }
}

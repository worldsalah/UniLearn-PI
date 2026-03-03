<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\User;
use App\Entity\UserBadge;
use App\Entity\UserLevel;
use App\Entity\UserPoints;
use App\Repository\BadgeRepository;
use App\Repository\UserBadgeRepository;
use App\Repository\UserLevelRepository;
use App\Repository\UserPointsRepository;
use Doctrine\ORM\EntityManagerInterface;

class GamificationService
{
    private EntityManagerInterface $entityManager;
    private UserLevelRepository $levelRepository;
    private BadgeRepository $badgeRepository;
    private UserBadgeRepository $userBadgeRepository;
    private UserPointsRepository $userPointsRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserLevelRepository $levelRepository,
        BadgeRepository $badgeRepository,
        UserBadgeRepository $userBadgeRepository,
        UserPointsRepository $userPointsRepository
    ) {
        $this->entityManager = $entityManager;
        $this->levelRepository = $levelRepository;
        $this->badgeRepository = $badgeRepository;
        $this->userBadgeRepository = $userBadgeRepository;
        $this->userPointsRepository = $userPointsRepository;
    }

    public function initializeUserGamification(User $user): void
    {
        $userId = $user->getId();
        if ($userId === null) {
            return;
        }
        $userPoints = $this->userPointsRepository->findByUser($userId);
        
        if ($userPoints === null) {
            $beginnerLevel = $this->levelRepository->findOneBy(['code' => 'BEGINNER']);
            
            // If no beginner level exists, create a default one
            if ($beginnerLevel === null) {
                $beginnerLevel = new UserLevel();
                $beginnerLevel->setName('Beginner');
                $beginnerLevel->setCode('BEGINNER');
                $beginnerLevel->setMinXp(0);
                $beginnerLevel->setMaxXp(100);
                $beginnerLevel->setColor('#6366f1');
                $beginnerLevel->setIcon('fas fa-seedling');
                $beginnerLevel->setLevelOrder(1);
                
                $this->entityManager->persist($beginnerLevel);
                $this->entityManager->flush();
            }
            
            $userPoints = new UserPoints();
            $userPoints->setUser($user);
            $userPoints->setTotalPoints(0);
            $userPoints->setCurrentLevelPoints(0);
            $userPoints->setCurrentLevel($beginnerLevel);
            $userPoints->setRankPosition(0);
            
            $this->entityManager->persist($userPoints);
            $this->entityManager->flush();
        }
    }

    public function addPoints(User $user, int $points, string $reason = ''): void
    {
        error_log('GamificationService::addPoints - User ID: ' . $user->getId());
        error_log('GamificationService::addPoints - Points to add: ' . $points);
        error_log('GamificationService::addPoints - Reason: ' . $reason);
        
        $userPoints = $this->getUserPoints($user);
        
        error_log('GamificationService::addPoints - Current points: ' . $userPoints->getTotalPoints());
        error_log('GamificationService::addPoints - Current level points: ' . $userPoints->getCurrentLevelPoints());
        
        $oldLevel = $userPoints->getCurrentLevel();
        $oldLevelName = $oldLevel !== null ? $oldLevel->getName() : 'None';
        
        // Add points to both total and current level
        $userPoints->addPoints($points);
        
        error_log('GamificationService::addPoints - New total points: ' . $userPoints->getTotalPoints());
        error_log('GamificationService::addPoints - New current level points: ' . $userPoints->getCurrentLevelPoints());
        
        // Check for level up
        $currentLevelPoints = $userPoints->getCurrentLevelPoints();
        $newLevel = $currentLevelPoints !== null ? $this->levelRepository->findByXpRange($currentLevelPoints) : null;
        if ($newLevel !== null && $newLevel !== $oldLevel) {
            error_log('GamificationService::addPoints - LEVEL UP! From ' . $oldLevelName . ' to ' . $newLevel->getName());
            
            // Reset current level points to the minimum of new level
            $minXp = $newLevel->getMinXp();
            $userPoints->setCurrentLevelPoints($minXp ?? 0);
            $userPoints->setCurrentLevel($newLevel);
            
            // Award level up badge
            $this->checkLevelUpBadges($user, $newLevel);
            
            // Award XP milestone badges
            $totalPoints = $userPoints->getTotalPoints();
            $this->checkXpMilestoneBadges($user, $totalPoints ?? 0);
        }
        
        $this->entityManager->flush();
        
        // Check for regular badges based on total XP
        $totalPoints = $userPoints->getTotalPoints();
        $this->checkBadges($user, $totalPoints ?? 0);
        
        // Update ranks
        $this->userPointsRepository->updateRanks();
        
        error_log('GamificationService::addPoints - Points added successfully');
    }

    public function getUserPoints(User $user): UserPoints
    {
        $userId = $user->getId();
        $userPoints = $userId !== null ? $this->userPointsRepository->findByUser($userId) : null;
        
        if ($userPoints === null) {
            $this->initializeUserGamification($user);
            $userId = $user->getId();
            $userPoints = $userId !== null ? $this->userPointsRepository->findByUser($userId) : null;
        }
        
        return $userPoints ?? new UserPoints();
    }

    public function getUserLevel(User $user): ?UserLevel
    {
        $userPoints = $this->getUserPoints($user);
        return $userPoints->getCurrentLevel();
    }

    public function getUserBadges(User $user): array
    {
        $userId = $user->getId();
        return $userId !== null ? $this->userBadgeRepository->findByUser($userId) : [];
    }

    public function getLeaderboard(int $limit = 10): array
    {
        return $this->userPointsRepository->getLeaderboard($limit);
    }

    public function getUserRank(User $user): int
    {
        $userId = $user->getId();
        return $userId !== null ? $this->userPointsRepository->getUserRank($userId) : 0;
    }

    private function checkBadges(User $user, int $totalPoints): void
    {
        $userId = $user->getId();
        $availableBadges = $this->badgeRepository->findActiveBadges();
        $userBadges = $userId !== null ? $this->userBadgeRepository->findByUser($userId) : [];
        $earnedBadgeIds = [];
        foreach ($userBadges as $userBadge) {
            $badgeId = $userBadge->getBadge()->getId();
            if ($badgeId !== null) {
                $earnedBadgeIds[] = $badgeId;
            }
        }

        foreach ($availableBadges as $badge) {
            $badgeId = $badge->getId();
            if ($badgeId !== null && !in_array($badgeId, $earnedBadgeIds, true) && $totalPoints >= $badge->getPointsRequired()) {
                $this->awardBadge($user, $badge, 'Points threshold reached');
            }
        }
    }

    private function checkLevelUpBadges(User $user, UserLevel $newLevel): void
    {
        $badgeCode = 'LEVEL_' . $newLevel->getCode();
        $badge = $this->badgeRepository->findByCode($badgeCode);
        
        $userId = $user->getId();
        $badgeId = $badge?->getId();
        $existingUserBadge = $userId !== null && $badgeId !== null ? $this->userBadgeRepository->findByUserAndBadge($userId, $badgeId) : null;
        if ($badge !== null && $existingUserBadge === null) {
            $this->awardBadge($user, $badge, 'Reached level: ' . $newLevel->getName());
        }
    }

    public function awardBadge(User $user, Badge $badge, string $reason = ''): void
    {
        $userId = $user->getId();
        $badgeId = $badge->getId();
        
        if ($userId === null || $badgeId === null) {
            return;
        }
        
        $existingUserBadge = $this->userBadgeRepository->findByUserAndBadge($userId, $badgeId);
        
        if ($existingUserBadge !== null) {
            return;
        }

        $userBadge = new UserBadge();
        $userBadge->setUser($user);
        $userBadge->setBadge($badge);
        $userBadge->setEarnedReason($reason);
        
        $this->entityManager->persist($userBadge);
        $this->entityManager->flush();
    }

    private function checkXpMilestoneBadges(User $user, int $totalPoints): void
    {
        $milestoneBadges = [
            100 => 'CENTURION',
            500 => 'HIGH_ACHIEVER', 
            1000 => 'MASTER',
            2500 => 'EXPERT',
            5000 => 'LEGEND',
            10000 => 'MYTHIC'
        ];
        
        foreach ($milestoneBadges as $milestone => $badgeCode) {
            if ($totalPoints >= $milestone) {
                $badge = $this->badgeRepository->findByCode($badgeCode);
                $userId = $user->getId();
                $badgeId = $badge?->getId();
                if ($badge !== null && $userId !== null && $badgeId !== null && $this->userBadgeRepository->findByUserAndBadge($userId, $badgeId) === null) {
                    $this->awardBadge($user, $badge, "Reached {$totalPoints} XP milestone");
                }
            }
        }
    }

    public function getProgressToNextLevel(User $user): array
    {
        $userPoints = $this->getUserPoints($user);
        $currentLevel = $userPoints->getCurrentLevel();
        
        if ($currentLevel === null) {
            return [
                'progress' => 0,
                'points_to_next' => 100,
                'current_points' => 0,
                'next_level' => 'Beginner'
            ];
        }

        $nextLevel = $this->levelRepository->getNextLevel($currentLevel);
        
        // Calculate progress within current level
        $levelMin = $currentLevel->getMinXp() ?? 0;
        $levelMax = $currentLevel->getMaxXp() ?? 0;
        $levelRange = $levelMax - $levelMin;
        
        if ($levelRange <= 0) {
            return [
                'progress' => 100,
                'points_to_next' => 0,
                'current_points' => $userPoints->getCurrentLevelPoints(),
                'next_level' => 'Max Level'
            ];
        }
        
        // Calculate progress percentage
        $currentLevelPoints = $userPoints->getCurrentLevelPoints() ?? 0;
        $progressInLevel = $currentLevelPoints - $levelMin;
        $progressPercentage = ($progressInLevel / $levelRange) * 100;
        
        return [
            'progress' => min(100, max(0, $progressPercentage)),
            'points_to_next' => $levelMax - $currentLevelPoints,
            'current_points' => $currentLevelPoints,
            'next_level' => $nextLevel !== null ? $nextLevel->getName() : 'Max Level'
        ];
    }

    public function awardCourseCompletionXP(User $user, int $courseId, string $courseTitle = ''): void
    {
        $xpAmount = 50; // Base XP for course completion
        $reason = "Completed course: " . ($courseTitle ?: "Course #{$courseId}");
        
        $this->addPoints($user, $xpAmount, $reason);
    }

    public function awardLessonCompletionXP(User $user, int $lessonId, string $lessonTitle = ''): void
    {
        $xpAmount = 10; // XP for lesson completion
        $reason = "Completed lesson: " . ($lessonTitle ?: "Lesson #{$lessonId}");
        
        $this->addPoints($user, $xpAmount, $reason);
    }

    public function awardQuizPassedXP(User $user, int $quizId, int $score, int $maxScore): void
    {
        // Calculate XP based on quiz performance
        $percentage = ($score / $maxScore) * 100;
        
        if ($percentage >= 90) {
            $xpAmount = 30; // Excellent performance
        } elseif ($percentage >= 80) {
            $xpAmount = 20; // Good performance
        } elseif ($percentage >= 70) {
            $xpAmount = 15; // Passing performance
        } else {
            $xpAmount = 10; // Basic completion
        }
        
        $reason = "Quiz passed with {$score}/{$maxScore} (" . round($percentage) . "%)";
        
        $this->addPoints($user, $xpAmount, $reason);
    }

    public function awardDailyStreakXP(User $user, int $streakDays): void
    {
        $xpAmount = $streakDays * 5; // 5 XP per streak day
        $reason = "Daily learning streak: {$streakDays} days";
        
        $this->addPoints($user, $xpAmount, $reason);
    }

    public function awardFirstCourseXP(User $user): void
    {
        $this->addPoints($user, 25, 'Completed first course');
    }

    public function getGamificationStats(User $user): array
    {
        $userPoints = $this->getUserPoints($user);
        $userBadges = $this->getUserBadges($user);
        
        return [
            'total_points' => $userPoints->getTotalPoints(),
            'current_level' => $userPoints->getCurrentLevel(),
            'rank' => $this->getUserRank($user),
            'badges_count' => count($userBadges),
            'progress' => $this->getProgressToNextLevel($user)
        ];
    }
}

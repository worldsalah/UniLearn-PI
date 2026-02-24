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
        $userPoints = $this->userPointsRepository->findByUser($user->getId());
        
        if (!$userPoints) {
            $beginnerLevel = $this->levelRepository->findOneBy(['code' => 'BEGINNER']);
            
            // If no beginner level exists, create a default one
            if (!$beginnerLevel) {
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
        
        $userPoints->addPoints($points);
        
        $newLevel = $this->levelRepository->findByXpRange($userPoints->getCurrentLevelPoints());
        if ($newLevel && $newLevel !== $userPoints->getCurrentLevel()) {
            $userPoints->setCurrentLevel($newLevel);
            $this->checkLevelUpBadges($user, $newLevel);
        }
        
        $this->entityManager->flush();
        $this->checkBadges($user, $userPoints->getTotalPoints());
        
        $this->userPointsRepository->updateRanks();
        
        error_log('GamificationService::addPoints - Points added successfully');
    }

    public function getUserPoints(User $user): UserPoints
    {
        $userPoints = $this->userPointsRepository->findByUser($user->getId());
        
        if (!$userPoints) {
            $this->initializeUserGamification($user);
            $userPoints = $this->userPointsRepository->findByUser($user->getId());
        }
        
        return $userPoints;
    }

    public function getUserLevel(User $user): ?UserLevel
    {
        $userPoints = $this->getUserPoints($user);
        return $userPoints->getCurrentLevel();
    }

    public function getUserBadges(User $user): array
    {
        return $this->userBadgeRepository->findByUser($user->getId());
    }

    public function getLeaderboard(int $limit = 10): array
    {
        return $this->userPointsRepository->getLeaderboard($limit);
    }

    public function getUserRank(User $user): int
    {
        return $this->userPointsRepository->getUserRank($user->getId());
    }

    private function checkBadges(User $user, int $totalPoints): void
    {
        $availableBadges = $this->badgeRepository->findActiveBadges();
        $userBadges = $this->userBadgeRepository->findByUser($user->getId());
        $earnedBadgeIds = [];
        foreach ($userBadges as $userBadge) {
            $earnedBadgeIds[] = $userBadge->getBadge()->getId();
        }

        foreach ($availableBadges as $badge) {
            if (!in_array($badge->getId(), $earnedBadgeIds) && $totalPoints >= $badge->getPointsRequired()) {
                $this->awardBadge($user, $badge, 'Points threshold reached');
            }
        }
    }

    private function checkLevelUpBadges(User $user, UserLevel $newLevel): void
    {
        $badgeCode = 'LEVEL_' . $newLevel->getCode();
        $badge = $this->badgeRepository->findByCode($badgeCode);
        
        if ($badge && !$this->userBadgeRepository->findByUserAndBadge($user->getId(), $badge->getId())) {
            $this->awardBadge($user, $badge, 'Reached level: ' . $newLevel->getName());
        }
    }

    public function awardBadge(User $user, Badge $badge, string $reason = ''): void
    {
        $existingUserBadge = $this->userBadgeRepository->findByUserAndBadge($user->getId(), $badge->getId());
        
        if ($existingUserBadge) {
            return;
        }

        $userBadge = new UserBadge();
        $userBadge->setUser($user);
        $userBadge->setBadge($badge);
        $userBadge->setEarnedReason($reason);
        
        $this->entityManager->persist($userBadge);
        $this->entityManager->flush();
    }

    public function getProgressToNextLevel(User $user): array
    {
        $userPoints = $this->getUserPoints($user);
        $currentLevel = $userPoints->getCurrentLevel();
        
        if (!$currentLevel) {
            return [
                'progress' => 0,
                'points_to_next' => 100,
                'current_points' => 0,
                'next_level' => 'Beginner'
            ];
        }

        $nextLevel = $this->levelRepository->getNextLevel($currentLevel);
        
        return [
            'progress' => $userPoints->getProgressToNextLevel(),
            'points_to_next' => $userPoints->getPointsToNextLevel(),
            'current_points' => $userPoints->getCurrentLevelPoints(),
            'next_level' => $nextLevel ? $nextLevel->getName() : 'Max Level'
        ];
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

<?php

namespace App\DataFixtures;

use App\Entity\Badge;
use App\Entity\UserLevel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GamificationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des niveaux
        $levels = [
            ['name' => 'Beginner', 'code' => 'BEGINNER', 'minXp' => 0, 'maxXp' => 100, 'color' => '#6366f1', 'icon' => 'fas fa-seedling', 'order' => 1],
            ['name' => 'Novice', 'code' => 'NOVICE', 'minXp' => 100, 'maxXp' => 250, 'color' => '#22c55e', 'icon' => 'fas fa-leaf', 'order' => 2],
            ['name' => 'Intermediate', 'code' => 'INTERMEDIATE', 'minXp' => 250, 'maxXp' => 500, 'color' => '#f59e0b', 'icon' => 'fas fa-fire', 'order' => 3],
            ['name' => 'Advanced', 'code' => 'ADVANCED', 'minXp' => 500, 'maxXp' => 1000, 'color' => '#ef4444', 'icon' => 'fas fa-rocket', 'order' => 4],
            ['name' => 'Expert', 'code' => 'EXPERT', 'minXp' => 1000, 'maxXp' => 2000, 'color' => '#8b5cf6', 'icon' => 'fas fa-crown', 'order' => 5],
            ['name' => 'Master', 'code' => 'MASTER', 'minXp' => 2000, 'maxXp' => 5000, 'color' => '#ec4899', 'icon' => 'fas fa-gem', 'order' => 6],
            ['name' => 'Legend', 'code' => 'LEGEND', 'minXp' => 5000, 'maxXp' => 999999, 'color' => '#f97316', 'icon' => 'fas fa-star', 'order' => 7],
        ];

        $createdLevels = [];
        foreach ($levels as $levelData) {
            $level = new UserLevel();
            $level->setName($levelData['name']);
            $level->setCode($levelData['code']);
            $level->setMinXp($levelData['minXp']);
            $level->setMaxXp($levelData['maxXp']);
            $level->setColor($levelData['color']);
            $level->setIcon($levelData['icon']);
            $level->setLevelOrder($levelData['order']);
            
            $manager->persist($level);
            $createdLevels[$levelData['code']] = $level;
        }

        // Création des badges
        $badges = [
            // Badges de progression
            ['name' => 'Premiers Pas', 'code' => 'FIRST_STEPS', 'description' => 'Commencer votre adventure d\'apprentissage', 'icon' => 'fas fa-shoe-prints', 'color' => '#6366f1', 'category' => 'milestone', 'points' => 0, 'order' => 1],
            ['name' => 'Apprenti Actif', 'code' => 'ACTIVE_LEARNER', 'description' => 'Compléter votre premier cours', 'icon' => 'fas fa-graduation-cap', 'color' => '#22c55e', 'category' => 'learning', 'points' => 50, 'order' => 2],
            ['name' => 'Explorateur', 'code' => 'EXPLORER', 'description' => 'Explorer 3 cours différents', 'icon' => 'fas fa-compass', 'color' => '#f59e0b', 'category' => 'learning', 'points' => 100, 'order' => 3],
            
            // Badges de niveau
            ['name' => 'Niveau Novice Atteint', 'code' => 'LEVEL_NOVICE', 'description' => 'Atteindre le niveau Novice', 'icon' => 'fas fa-leaf', 'color' => '#22c55e', 'category' => 'achievement', 'points' => 100, 'order' => 10],
            ['name' => 'Niveau Intermediate Atteint', 'code' => 'LEVEL_INTERMEDIATE', 'description' => 'Atteindre le niveau Intermediate', 'icon' => 'fas fa-fire', 'color' => '#f59e0b', 'category' => 'achievement', 'points' => 250, 'order' => 11],
            ['name' => 'Niveau Advanced Atteint', 'code' => 'LEVEL_ADVANCED', 'description' => 'Atteindre le niveau Advanced', 'icon' => 'fas fa-rocket', 'color' => '#ef4444', 'category' => 'achievement', 'points' => 500, 'order' => 12],
            ['name' => 'Niveau Expert Atteint', 'code' => 'LEVEL_EXPERT', 'description' => 'Atteindre le niveau Expert', 'icon' => 'fas fa-crown', 'color' => '#8b5cf6', 'category' => 'achievement', 'points' => 1000, 'order' => 13],
            
            // Badges de points
            ['name' => '100 XP', 'code' => 'XP_100', 'description' => 'Accumuler 100 points XP', 'icon' => 'fas fa-star', 'color' => '#fbbf24', 'category' => 'milestone', 'points' => 100, 'order' => 20],
            ['name' => '500 XP', 'code' => 'XP_500', 'description' => 'Accumuler 500 points XP', 'icon' => 'fas fa-star', 'color' => '#fbbf24', 'category' => 'milestone', 'points' => 500, 'order' => 21],
            ['name' => '1000 XP', 'code' => 'XP_1000', 'description' => 'Accumuler 1000 points XP', 'icon' => 'fas fa-star', 'color' => '#fbbf24', 'category' => 'milestone', 'points' => 1000, 'order' => 22],
            
            // Badges de participation
            ['name' => 'Participant', 'code' => 'PARTICIPANT', 'description' => 'Participer à un quiz', 'icon' => 'fas fa-question-circle', 'color' => '#06b6d4', 'category' => 'participation', 'points' => 25, 'order' => 30],
            ['name' => 'Quiz Master', 'code' => 'QUIZ_MASTER', 'description' => 'Réussir 10 quizzes', 'icon' => 'fas fa-brain', 'color' => '#06b6d4', 'category' => 'participation', 'points' => 200, 'order' => 31],
            
            // Badges spéciaux
            ['name' => 'Dévotion', 'code' => 'DEVOTION', 'description' => 'Se connecter 7 jours consécutifs', 'icon' => 'fas fa-heart', 'color' => '#ef4444', 'category' => 'participation', 'points' => 150, 'order' => 40],
            ['name' => 'Curieux', 'code' => 'CURIOUS', 'description' => 'Poser 5 questions', 'icon' => 'fas fa-question', 'color' => '#8b5cf6', 'category' => 'participation', 'points' => 75, 'order' => 41],
            ['name' => 'Partageur', 'code' => 'SHARER', 'description' => 'Partager votre progression', 'icon' => 'fas fa-share-alt', 'color' => '#22c55e', 'category' => 'participation', 'points' => 100, 'order' => 42],
        ];

        foreach ($badges as $badgeData) {
            $badge = new Badge();
            $badge->setName($badgeData['name']);
            $badge->setCode($badgeData['code']);
            $badge->setDescription($badgeData['description']);
            $badge->setIcon($badgeData['icon']);
            $badge->setColor($badgeData['color']);
            $badge->setCategory($badgeData['category']);
            $badge->setPointsRequired($badgeData['points']);
            $badge->setBadgeOrder($badgeData['order']);
            $badge->setIsActive(true);
            
            $manager->persist($badge);
        }

        $manager->flush();
    }
}

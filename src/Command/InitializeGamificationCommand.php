<?php

namespace App\Command;

use App\Entity\Badge;
use App\Entity\UserLevel;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:init-gamification',
    description: 'Initialize gamification system with default levels and badges'
)]
class InitializeGamificationCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private GamificationService $gamificationService;

    public function __construct(EntityManagerInterface $entityManager, GamificationService $gamificationService)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->gamificationService = $gamificationService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Initializing gamification system...');

        // Create default levels
        $this->createDefaultLevels($output);
        
        // Create default badges
        $this->createDefaultBadges($output);

        $output->writeln('Gamification system initialized successfully!');
        return Command::SUCCESS;
    }

    private function createDefaultLevels(OutputInterface $output): void
    {
        $levels = [
            [
                'name' => 'Beginner',
                'code' => 'BEGINNER',
                'minXp' => 0,
                'maxXp' => 100,
                'color' => '#6366f1',
                'icon' => 'fas fa-seedling',
                'levelOrder' => 1
            ],
            [
                'name' => 'Novice',
                'code' => 'NOVICE',
                'minXp' => 101,
                'maxXp' => 250,
                'color' => '#22c55e',
                'icon' => 'fas fa-seedling',
                'levelOrder' => 2
            ],
            [
                'name' => 'Intermediate',
                'code' => 'INTERMEDIATE',
                'minXp' => 251,
                'maxXp' => 500,
                'color' => '#f59e0b',
                'icon' => 'fas fa-fire',
                'levelOrder' => 3
            ],
            [
                'name' => 'Advanced',
                'code' => 'ADVANCED',
                'minXp' => 501,
                'maxXp' => 1000,
                'color' => '#ef4444',
                'icon' => 'fas fa-star',
                'levelOrder' => 4
            ],
            [
                'name' => 'Expert',
                'code' => 'EXPERT',
                'minXp' => 1001,
                'maxXp' => 2500,
                'color' => '#8b5cf6',
                'icon' => 'fas fa-crown',
                'levelOrder' => 5
            ],
            [
                'name' => 'Master',
                'code' => 'MASTER',
                'minXp' => 2501,
                'maxXp' => 5000,
                'color' => '#ec4899',
                'icon' => 'fas fa-trophy',
                'levelOrder' => 6
            ],
            [
                'name' => 'Legend',
                'code' => 'LEGEND',
                'minXp' => 5001,
                'maxXp' => 10000,
                'color' => '#a855f7',
                'icon' => 'fas fa-medal',
                'levelOrder' => 7
            ],
            [
                'name' => 'Mythic',
                'code' => 'MYTHIC',
                'minXp' => 10001,
                'maxXp' => 999999,
                'color' => '#f97316',
                'icon' => 'fas fa-gem',
                'levelOrder' => 8
            ]
        ];

        $levelRepository = $this->entityManager->getRepository(UserLevel::class);
        
        foreach ($levels as $levelData) {
            $existingLevel = $levelRepository->findOneBy(['code' => $levelData['code']]);
            
            if ($existingLevel === null) {
                $level = new UserLevel();
                $level->setName($levelData['name']);
                $level->setCode($levelData['code']);
                $level->setMinXp($levelData['minXp']);
                $level->setMaxXp($levelData['maxXp']);
                $level->setColor($levelData['color']);
                $level->setIcon($levelData['icon']);
                $level->setLevelOrder($levelData['levelOrder']);
                
                $this->entityManager->persist($level);
                $output->writeln("Created level: {$levelData['name']}");
            } else {
                $output->writeln("Level already exists: {$levelData['name']}");
            }
        }
        
        $this->entityManager->flush();
    }

    private function createDefaultBadges(OutputInterface $output): void
    {
        $badges = [
            [
                'name' => 'First Steps',
                'code' => 'FIRST_STEPS',
                'description' => 'Completed your first lesson',
                'pointsRequired' => 0,
                'color' => '#6366f1',
                'icon' => 'fas fa-play-circle'
            ],
            [
                'name' => 'Quick Learner',
                'code' => 'QUICK_LEARNER',
                'description' => 'Completed 5 lessons in one day',
                'pointsRequired' => 50,
                'color' => '#22c55e',
                'icon' => 'fas fa-bolt'
            ],
            [
                'name' => 'Centurion',
                'code' => 'CENTURION',
                'description' => 'Reached 100 XP',
                'pointsRequired' => 100,
                'color' => '#f59e0b',
                'icon' => 'fas fa-award'
            ],
            [
                'name' => 'High Achiever',
                'code' => 'HIGH_ACHIEVER',
                'description' => 'Reached 500 XP',
                'pointsRequired' => 500,
                'color' => '#ef4444',
                'icon' => 'fas fa-star'
            ],
            [
                'name' => 'Master Mind',
                'code' => 'MASTER',
                'description' => 'Reached 1000 XP',
                'pointsRequired' => 1000,
                'color' => '#8b5cf6',
                'icon' => 'fas fa-brain'
            ],
            [
                'name' => 'Expert Status',
                'code' => 'EXPERT',
                'description' => 'Reached 2500 XP',
                'pointsRequired' => 2500,
                'color' => '#ec4899',
                'icon' => 'fas fa-graduation-cap'
            ],
            [
                'name' => 'Legend',
                'code' => 'LEGEND',
                'description' => 'Reached 5000 XP',
                'pointsRequired' => 5000,
                'color' => '#a855f7',
                'icon' => 'fas fa-crown'
            ],
            [
                'name' => 'Mythic',
                'code' => 'MYTHIC',
                'description' => 'Reached 10000 XP',
                'pointsRequired' => 10000,
                'color' => '#f97316',
                'icon' => 'fas fa-gem'
            ],
            // Level-specific badges
            [
                'name' => 'Novice Level',
                'code' => 'LEVEL_NOVICE',
                'description' => 'Reached Novice level',
                'pointsRequired' => 101,
                'color' => '#22c55e',
                'icon' => 'fas fa-seedling'
            ],
            [
                'name' => 'Intermediate Level',
                'code' => 'LEVEL_INTERMEDIATE',
                'description' => 'Reached Intermediate level',
                'pointsRequired' => 251,
                'color' => '#f59e0b',
                'icon' => 'fas fa-fire'
            ],
            [
                'name' => 'Advanced Level',
                'code' => 'LEVEL_ADVANCED',
                'description' => 'Reached Advanced level',
                'pointsRequired' => 501,
                'color' => '#ef4444',
                'icon' => 'fas fa-star'
            ],
            [
                'name' => 'Expert Level',
                'code' => 'LEVEL_EXPERT',
                'description' => 'Reached Expert level',
                'pointsRequired' => 1001,
                'color' => '#8b5cf6',
                'icon' => 'fas fa-crown'
            ],
            [
                'name' => 'Master Level',
                'code' => 'LEVEL_MASTER',
                'description' => 'Reached Master level',
                'pointsRequired' => 2501,
                'color' => '#ec4899',
                'icon' => 'fas fa-trophy'
            ],
            [
                'name' => 'Legend Level',
                'code' => 'LEVEL_LEGEND',
                'description' => 'Reached Legend level',
                'pointsRequired' => 5001,
                'color' => '#a855f7',
                'icon' => 'fas fa-medal'
            ],
            [
                'name' => 'Mythic Level',
                'code' => 'LEVEL_MYTHIC',
                'description' => 'Reached Mythic level',
                'pointsRequired' => 10001,
                'color' => '#f97316',
                'icon' => 'fas fa-gem'
            ]
        ];

        $badgeRepository = $this->entityManager->getRepository(Badge::class);
        
        foreach ($badges as $badgeData) {
            $existingBadge = $badgeRepository->findOneBy(['code' => $badgeData['code']]);
            
            if ($existingBadge === null) {
                $badge = new Badge();
                $badge->setName($badgeData['name']);
                $badge->setCode($badgeData['code']);
                $badge->setDescription($badgeData['description']);
                $badge->setPointsRequired($badgeData['pointsRequired']);
                $badge->setColor($badgeData['color']);
                $badge->setIcon($badgeData['icon']);
                
                $this->entityManager->persist($badge);
                $output->writeln("Created badge: {$badgeData['name']}");
            } else {
                $output->writeln("Badge already exists: {$badgeData['name']}");
            }
        }
        
        $this->entityManager->flush();
    }
}

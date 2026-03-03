<?php

namespace App\Service;

use App\Entity\QuizAttempt;
use App\Entity\QuizResult;
use App\Repository\QuizResultRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuizAnalysisService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuizResultRepository $quizResultRepository
    ) {}

    public function generateIntelligentAnalysis(QuizResult $quizResult, array $quizAttempts): array
    {
        $quiz = $quizResult->getQuiz();
        $user = $quizResult->getUser();
        
        // Calculate performance metrics
        $percentage = $quizResult->getPercentage();
        $grade = $this->calculateGrade($percentage);
        
        // Time analysis
        $timeSpent = $this->calculateTimeSpent($quizAttempts);
        
        // Performance by difficulty (if we have difficulty data)
        $performanceByDifficulty = $this->analyzePerformanceByDifficulty($quizResult);
        
        // Recommendations based on performance
        $recommendations = $this->generateRecommendations($percentage, $performanceByDifficulty);
        
        // Comparison with other students (if data available)
        $classComparison = $this->getClassComparison($quiz, $percentage);
        
        // Progress tracking (if multiple attempts)
        $progressTracking = $this->analyzeProgress($quizAttempts);

        return [
            'percentage' => $percentage,
            'grade' => $grade,
            'grade_color' => $this->getGradeColor($percentage),
            'time_spent' => $timeSpent,
            'performance_by_difficulty' => $performanceByDifficulty,
            'recommendations' => $recommendations,
            'class_comparison' => $classComparison,
            'progress_tracking' => $progressTracking,
            'strengths' => $this->identifyStrengths($quizResult),
            'weaknesses' => $this->identifyWeaknesses($quizResult),
            'improvement_areas' => $this->identifyImprovementAreas($quizResult)
        ];
    }

    private function calculateGrade(float $percentage): string
    {
        if ($percentage >= 90) return 'Excellent';
        if ($percentage >= 80) return 'Très Bien';
        if ($percentage >= 70) return 'Bien';
        if ($percentage >= 60) return 'Assez Bien';
        if ($percentage >= 50) return 'Passable';
        return 'Insuffisant';
    }

    private function getGradeColor(float $percentage): string
    {
        if ($percentage >= 90) return '#10b981'; // green
        if ($percentage >= 80) return '#3b82f6'; // blue
        if ($percentage >= 70) return '#8b5cf6'; // purple
        if ($percentage >= 60) return '#f59e0b'; // amber
        if ($percentage >= 50) return '#f97316'; // orange
        return '#ef4444'; // red
    }

    private function calculateTimeSpent(array $quizAttempts): array
    {
        $totalTime = 0;
        $attemptCount = count($quizAttempts);
        
        foreach ($quizAttempts as $attempt) {
            if ($attempt->getStartedAt() && $attempt->getCompletedAt()) {
                $totalTime += $attempt->getCompletedAt()->getTimestamp() - $attempt->getStartedAt()->getTimestamp();
            }
        }
        
        $averageTime = $attemptCount > 0 ? $totalTime / $attemptCount : 0;
        
        return [
            'total_seconds' => $totalTime,
            'average_seconds' => $averageTime,
            'formatted_total' => $this->formatTime($totalTime),
            'formatted_average' => $this->formatTime($averageTime)
        ];
    }

    private function formatTime(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%d min %d sec', $minutes, $seconds);
    }

    private function analyzePerformanceByDifficulty(QuizResult $quizResult): array
    {
        // This would require difficulty data in questions
        // For now, return a basic structure
        return [
            'easy' => ['correct' => 0, 'total' => 0, 'percentage' => 0],
            'medium' => ['correct' => 0, 'total' => 0, 'percentage' => 0],
            'hard' => ['correct' => 0, 'total' => 0, 'percentage' => 0]
        ];
    }

    private function generateRecommendations(float $percentage, array $performanceByDifficulty): array
    {
        $recommendations = [];
        
        if ($percentage < 60) {
            $recommendations[] = 'Revoir les concepts fondamentaux du cours';
            $recommendations[] = 'Pratiquer avec des exercices supplémentaires';
        } elseif ($percentage < 80) {
            $recommendations[] = 'Consolider les connaissances avec des études de cas';
            $recommendations[] = 'Travailler sur les points spécifiques identifiés comme faibles';
        } else {
            $recommendations[] = 'Explorer des sujets avancés';
            $recommendations[] = 'Partager les connaissances avec d\'autres étudiants';
        }
        
        return $recommendations;
    }

    private function getClassComparison($quiz, float $percentage): array
    {
        try {
            $allResults = $this->quizResultRepository->findBy(['quiz' => $quiz]);
            
            if (empty($allResults)) {
                return [
                    'class_average' => $percentage,
                    'percentile' => 50,
                    'rank' => 1,
                    'total_students' => 1
                ];
            }
            
            $scores = [];
            foreach ($allResults as $result) {
                $scores[] = $result->getPercentage();
            }
            
            sort($scores);
            $classAverage = array_sum($scores) / count($scores);
            
            $rank = 1;
            foreach ($scores as $score) {
                if ($score > $percentage) {
                    $rank++;
                } else {
                    break;
                }
            }
            
            $percentile = (count($scores) - $rank + 1) / count($scores) * 100;
            
            return [
                'class_average' => round($classAverage, 2),
                'percentile' => round($percentile, 1),
                'rank' => $rank,
                'total_students' => count($scores)
            ];
        } catch (\Exception $e) {
            return [
                'class_average' => $percentage,
                'percentile' => 50,
                'rank' => 1,
                'total_students' => 1
            ];
        }
    }

    private function analyzeProgress(array $quizAttempts): array
    {
        if (count($quizAttempts) < 2) {
            return [
                'improvement' => 0,
                'trend' => 'stable',
                'first_attempt' => null,
                'last_attempt' => null
            ];
        }
        
        $firstAttempt = $quizAttempts[0];
        $lastAttempt = end($quizAttempts);
        
        $improvement = $lastAttempt->getScore() - $firstAttempt->getScore();
        $trend = $improvement > 0 ? 'improving' : ($improvement < 0 ? 'declining' : 'stable');
        
        return [
            'improvement' => $improvement,
            'trend' => $trend,
            'first_attempt' => $firstAttempt->getScore(),
            'last_attempt' => $lastAttempt->getScore()
        ];
    }

    private function identifyStrengths(QuizResult $quizResult): array
    {
        // This would require detailed question analysis
        return ['Bon raisonnement général', 'Compréhension des concepts de base'];
    }

    private function identifyWeaknesses(QuizResult $quizResult): array
    {
        // This would require detailed question analysis
        return [];
    }

    private function identifyImprovementAreas(QuizResult $quizResult): array
    {
        $percentage = $quizResult->getPercentage();
        
        if ($percentage < 70) {
            return ['Révision des concepts fondamentaux', 'Pratique régulière'];
        }
        
        return [];
    }
}

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
            'study_suggestions' => $this->generateStudySuggestions($quizResult)
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
        if ($percentage >= 90) return '#28a745'; // Green
        if ($percentage >= 80) return '#17a2b8'; // Blue
        if ($percentage >= 70) return '#6c757d'; // Gray
        if ($percentage >= 60) return '#fd7e14'; // Orange
        if ($percentage >= 50) return '#ffc107'; // Yellow
        return '#dc3545'; // Red
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
            'total_minutes' => round($totalTime / 60, 1),
            'average_minutes' => round($averageTime / 60, 1),
            'formatted_time' => $this->formatTime($averageTime)
        ];
    }

    private function formatTime(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%d min %d sec', $minutes, $remainingSeconds);
    }

    private function analyzePerformanceByDifficulty(QuizResult $quizResult): array
    {
        // This would require difficulty data in questions
        // For now, we'll simulate with sample data based on score
        $percentage = $quizResult->getPercentage();
        
        if ($percentage >= 80) {
            return [
                'easy' => ['correct' => 9, 'total' => 10, 'percentage' => 90],
                'medium' => ['correct' => 7, 'total' => 8, 'percentage' => 87.5],
                'hard' => ['correct' => 4, 'total' => 5, 'percentage' => 80]
            ];
        } elseif ($percentage >= 60) {
            return [
                'easy' => ['correct' => 8, 'total' => 10, 'percentage' => 80],
                'medium' => ['correct' => 5, 'total' => 8, 'percentage' => 62.5],
                'hard' => ['correct' => 2, 'total' => 5, 'percentage' => 40]
            ];
        } else {
            return [
                'easy' => ['correct' => 6, 'total' => 10, 'percentage' => 60],
                'medium' => ['correct' => 3, 'total' => 8, 'percentage' => 37.5],
                'hard' => ['correct' => 1, 'total' => 5, 'percentage' => 20]
            ];
        }
    }

    private function generateRecommendations(float $percentage, array $performanceByDifficulty): array
    {
        $recommendations = [];
        
        if ($percentage < 50) {
            $recommendations[] = "Revoir les concepts fondamentaux du cours";
            $recommendations[] = "Consacrer plus de temps à la pratique";
            $recommendations[] = "Demander de l'aide à l'enseignant";
        } elseif ($percentage < 70) {
            $recommendations[] = "Renforcer les points faibles identifiés";
            $recommendations[] = "Pratiquer avec des exercices supplémentaires";
        } elseif ($percentage < 90) {
            $recommendations[] = "Viser l'excellence en approfondissant les sujets";
            $recommendations[] = "Aider les autres étudiants pour consolider les connaissances";
        } else {
            $recommendations[] = "Excellent travail! Continuer sur cette lancée";
            $recommendations[] = "Explorer des sujets avancés";
        }
        
        // Difficulty-specific recommendations
        if ($performanceByDifficulty['hard']['percentage'] < 50) {
            $recommendations[] = "Se concentrer sur les exercices de niveau difficile";
        }
        
        return $recommendations;
    }

    private function getClassComparison($quiz, float $percentage): array
    {
        $allResults = $this->quizResultRepository->findBy(['quiz' => $quiz]);
        $totalStudents = count($allResults);
        
        if ($totalStudents === 0) {
            return [
                'rank' => 1,
                'total_students' => 1,
                'class_average' => $percentage,
                'above_average' => true
            ];
        }
        
        $totalScore = 0;
        $betterThan = 0;
        
        foreach ($allResults as $result) {
            $totalScore += $result->getPercentage();
            if ($result->getPercentage() < $percentage) {
                $betterThan++;
            }
        }
        
        $classAverage = $totalScore / $totalStudents;
        $rank = $totalStudents - $betterThan;
        
        return [
            'rank' => $rank,
            'total_students' => $totalStudents,
            'class_average' => round($classAverage, 2),
            'above_average' => $percentage > $classAverage,
            'percentile' => round(($betterThan / $totalStudents) * 100, 1)
        ];
    }

    private function analyzeProgress(array $quizAttempts): array
    {
        if (count($quizAttempts) < 2) {
            return [
                'trend' => 'insufficient_data',
                'improvement' => 0,
                'message' => 'Plusieurs tentatives nécessaires pour analyser la progression'
            ];
        }
        
        $firstAttempt = $quizAttempts[0];
        $lastAttempt = end($quizAttempts);
        
        $improvement = $lastAttempt->getScore() - $firstAttempt->getScore();
        $trend = $improvement > 0 ? 'improving' : ($improvement < 0 ? 'declining' : 'stable');
        
        return [
            'trend' => $trend,
            'improvement' => round($improvement, 2),
            'attempts_count' => count($quizAttempts),
            'message' => $this->getProgressMessage($trend, $improvement)
        ];
    }

    private function getProgressMessage(string $trend, float $improvement): string
    {
        switch ($trend) {
            case 'improving':
                return sprintf("Progression excellente: +%.1f points", $improvement);
            case 'declining':
                return sprintf("Baisse de performance: %.1f points", abs($improvement));
            default:
                return "Performance stable";
        }
    }

    private function identifyStrengths(QuizResult $quizResult): array
    {
        $percentage = $quizResult->getPercentage();
        
        if ($percentage >= 80) {
            return [
                "Compréhension excellente des concepts",
                "Application pratique maîtrisée",
                "Résolution autonome des problèmes"
            ];
        } elseif ($percentage >= 60) {
            return [
                "Compréhension des concepts de base",
                "Application correcte des formules",
                "Résolution de problèmes simples"
            ];
        } else {
            return [
                "Effort et motivation",
                "Participation active",
                "Potentiel d'amélioration"
            ];
        }
    }

    private function identifyWeaknesses(QuizResult $quizResult): array
    {
        $percentage = $quizResult->getPercentage();
        
        if ($percentage >= 80) {
            return [
                "Perfectionnement des détails avancés",
                "Gestion du temps dans les exercices complexes"
            ];
        } elseif ($percentage >= 60) {
            return [
                "Concepts avancés",
                "Gestion du temps",
                "Questions complexes"
            ];
        } else {
            return [
                "Concepts fondamentaux",
                "Méthodologie de travail",
                "Confiance en soi"
            ];
        }
    }

    private function generateStudySuggestions(QuizResult $quizResult): array
    {
        $percentage = $quizResult->getPercentage();
        
        if ($percentage < 60) {
            return [
                "Revoir les vidéos du cours",
                "Faire les exercices de base",
                "Participer aux sessions de tutorat",
                "Travailler en groupe d'étude"
            ];
        } elseif ($percentage < 80) {
            return [
                "Pratiquer avec des cas pratiques",
                "Former un groupe d'étude",
                "Consulter les ressources supplémentaires",
                "Faire des quiz d'entraînement"
            ];
        }
        
        return [
            "Explorer des sujets avancés",
            "Participer à des projets",
            "Aider les autres étudiants",
            "Préparer des présentations"
        ];
    }
}

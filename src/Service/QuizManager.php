<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Entity\QuizSettings;

/**
 * Service métier pour la gestion des quiz
 * Règles métier:
 * 1. Le titre est obligatoire
 * 2. Le score de passage doit être entre 0 et 100
 * 3. Le temps limite doit être positif si défini
 */
class QuizManager
{
    public function validate(Quiz $quiz): bool
    {
        if (empty($quiz->getTitle())) {
            throw new \InvalidArgumentException('Le titre du quiz est obligatoire');
        }
        
        return true;
    }
    
    public function validateSettings(QuizSettings $settings): bool
    {
        $passingScore = $settings->getPassingScore();
        if ($passingScore !== null && ($passingScore < 0 || $passingScore > 100)) {
            throw new \InvalidArgumentException('Le score de passage doit être entre 0 et 100');
        }
        
        $timeLimit = $settings->getTimeLimit();
        if ($timeLimit !== null && $timeLimit <= 0) {
            throw new \InvalidArgumentException('Le temps limite doit être positif');
        }
        
        return true;
    }
    
    public function calculateScore(int $correctAnswers, int $totalQuestions): float
    {
        if ($totalQuestions <= 0) {
            throw new \InvalidArgumentException('Le nombre total de questions doit être positif');
        }
        
        return ($correctAnswers / $totalQuestions) * 100;
    }
    
    public function hasPassed(QuizSettings $settings, float $score): bool
    {
        $passingScore = $settings->getPassingScore() ?? 70;
        return $score >= $passingScore;
    }
}

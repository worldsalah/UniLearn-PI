<?php

namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\User;
use App\Entity\Course;

/**
 * Service métier pour la gestion des inscriptions
 * Règles métier:
 * 1. L'utilisateur est obligatoire
 * 2. Le cours est obligatoire
 * 3. L'utilisateur ne peut pas s'inscrire deux fois au même cours
 */
class EnrollmentManager
{
    public function validate(Enrollment $enrollment): bool
    {
        if ($enrollment->getUser() === null) {
            throw new \InvalidArgumentException('L\'utilisateur est obligatoire');
        }
        
        if ($enrollment->getCourse() === null) {
            throw new \InvalidArgumentException('Le cours est obligatoire');
        }
        
        return true;
    }
    
    public function canEnroll(User $user, Course $course): bool
    {
        // Vérifier si l'utilisateur est actif
        if ($user->getStatus() !== 'active') {
            throw new \InvalidArgumentException('L\'utilisateur doit être actif pour s\'inscrire');
        }
        
        return true;
    }
    
    public function calculateProgress(int $completedLessons, int $totalLessons): float
    {
        if ($totalLessons <= 0) {
            return 0;
        }
        
        return round(($completedLessons / $totalLessons) * 100, 2);
    }
}

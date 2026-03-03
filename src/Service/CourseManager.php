<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;

/**
 * Service métier pour la gestion des cours
 * Règles métier:
 * 1. Le titre du cours est obligatoire
 * 2. Le prix doit être positif
 * 3. La description courte doit avoir au moins 20 caractères
 */
class CourseManager
{
    public function validate(Course $course): bool
    {
        if (empty($course->getTitle())) {
            throw new \InvalidArgumentException('Le titre du cours est obligatoire');
        }
        
        if ($course->getPrice() === null || $course->getPrice() < 0) {
            throw new \InvalidArgumentException('Le prix doit être un nombre positif');
        }
        
        if (empty($course->getShortDescription())) {
            throw new \InvalidArgumentException('La description courte est obligatoire');
        }
        
        if (strlen($course->getShortDescription()) < 20) {
            throw new \InvalidArgumentException('La description courte doit contenir au moins 20 caractères');
        }
        
        return true;
    }
    
    public function canEnroll(Course $course, User $user): bool
    {
        if ($course->getPrice() > 0 && $user->getIncome() < $course->getPrice()) {
            throw new \InvalidArgumentException('Solde insuffisant pour s\'inscrire à ce cours');
        }
        
        return true;
    }
}

<?php

namespace App\Service;

use App\Entity\User;

/**
 * Service métier pour la gestion des utilisateurs
 * Règles métier:
 * 1. Le nom complet est obligatoire
 * 2. L'email doit être valide
 * 3. Le mot de passe doit avoir au moins 8 caractères
 */
class UserManager
{
    public function validate(User $user): bool
    {
        if (empty($user->getFullName())) {
            throw new \InvalidArgumentException('Le nom complet est obligatoire');
        }
        
        if (empty($user->getEmail())) {
            throw new \InvalidArgumentException('L\'email est obligatoire');
        }
        
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'email n\'est pas valide');
        }
        
        if (empty($user->getPassword())) {
            throw new \InvalidArgumentException('Le mot de passe est obligatoire');
        }
        
        if (strlen($user->getPassword()) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères');
        }
        
        return true;
    }
    
    public function validatePassword(string $password): bool
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères');
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins une majuscule');
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins une minuscule');
        }
        
        if (!preg_match('/\d/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins un chiffre');
        }
        
        if (!preg_match('/[@$!%*?&]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins un caractère spécial (@$!%*?&)');
        }
        
        return true;
    }
}

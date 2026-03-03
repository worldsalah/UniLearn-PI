<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour UserManager
 * Règles métier validées:
 * 1. Le nom complet est obligatoire
 * 2. L'email doit être valide
 * 3. Le mot de passe doit avoir au moins 8 caractères
 */
class UserManagerTest extends TestCase
{
    public function testValidUser(): void
    {
        $user = new User();
        $user->setFullName('Jean Dupont');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('SecurePass123!');
        
        $manager = new UserManager();
        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithoutFullName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom complet est obligatoire');
        
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('SecurePass123!');
        
        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email n\'est pas valide');
        
        $user = new User();
        $user->setFullName('Test User');
        $user->setEmail('invalid-email');
        $user->setPassword('SecurePass123!');
        
        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithoutEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email est obligatoire');
        
        $user = new User();
        $user->setFullName('Test User');
        $user->setPassword('SecurePass123!');
        
        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithoutPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe est obligatoire');
        
        $user = new User();
        $user->setFullName('Test User');
        $user->setEmail('test@example.com');
        
        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 8 caractères');
        
        $user = new User();
        $user->setFullName('Test User');
        $user->setEmail('test@example.com');
        $user->setPassword('short');
        
        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testValidatePasswordWithAllRequirements(): void
    {
        $manager = new UserManager();
        $this->assertTrue($manager->validatePassword('SecurePass123!'));
    }

    public function testValidatePasswordWithoutUppercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins une majuscule');
        
        $manager = new UserManager();
        $manager->validatePassword('securepass123!');
    }

    public function testValidatePasswordWithoutLowercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins une minuscule');
        
        $manager = new UserManager();
        $manager->validatePassword('SECUREPASS123!');
    }

    public function testValidatePasswordWithoutDigit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins un chiffre');
        
        $manager = new UserManager();
        $manager->validatePassword('SecurePass!');
    }

    public function testValidatePasswordWithoutSpecialChar(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins un caractère spécial');
        
        $manager = new UserManager();
        $manager->validatePassword('SecurePass123');
    }
}

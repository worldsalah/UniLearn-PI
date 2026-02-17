#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

echo "👥 Creating Test Users for Course Lifecycle System\n";
echo "================================================\n\n";

// Get Symfony container
$container = new ContainerBuilder();
$loader = new \Symfony\Component\DependencyInjection\Loader\PhpFileLoader($container, new \Symfony\Component\Config\FileLocator(__DIR__ . '/../config'));
$loader->load('services.php');

// Get required services
$entityManager = $container->get(EntityManagerInterface::class);
$passwordHasher = $container->get(UserPasswordHasherInterface::class);

// Create test users
$testUsers = [
    [
        'email' => 'admin@test.com',
        'password' => 'admin123',
        'roles' => ['ROLE_ADMIN'],
        'fullName' => 'Test Administrator'
    ],
    [
        'email' => 'instructor@test.com', 
        'password' => 'instructor123',
        'roles' => ['ROLE_INSTRUCTOR'],
        'fullName' => 'Test Instructor'
    ],
    [
        'email' => 'student@test.com',
        'password' => 'student123', 
        'roles' => ['ROLE_STUDENT'],
        'fullName' => 'Test Student'
    ]
];

foreach ($testUsers as $userData) {
    // Check if user already exists
    $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
    
    if ($existingUser) {
        echo "✅ User {$userData['email']} already exists\n";
        continue;
    }
    
    // Create new user
    $user = new User();
    $user->setEmail($userData['email']);
    $user->setRoles($userData['roles']);
    
    // Hash password
    $hashedPassword = $passwordHasher->hashPassword($user, $userData['password']);
    $user->setPassword($hashedPassword);
    
    // Set full name if the entity has this method
    if (method_exists($user, 'setFullName')) {
        $user->setFullName($userData['fullName']);
    }
    
    $entityManager->persist($user);
    $entityManager->flush();
    
    echo "✅ Created user: {$userData['email']} (Role: " . implode(', ', $userData['roles']) . ")\n";
}

echo "\n🎯 Test Users Created Successfully!\n\n";

echo "📋 Login Credentials:\n";
echo "┌─────────────────────┬─────────────┬─────────────┐\n";
echo "│ Email               │ Password    │ Role        │\n";
echo "├─────────────────────┼─────────────┼─────────────┤\n";
echo "│ admin@test.com      │ admin123    │ ADMIN       │\n";
echo "│ instructor@test.com │ instructor123│ INSTRUCTOR  │\n";
echo "│ student@test.com    │ student123  │ STUDENT     │\n";
echo "└─────────────────────┴─────────────┴─────────────┘\n\n";

echo "🌐 Testing URLs:\n";
echo "1. Admin Dashboard: http://localhost:8000/admin/courses\n";
echo "2. API Transitions: http://localhost:8000/api/courses/transitions\n";
echo "3. Login Page: http://localhost:8000/login\n\n";

echo "🔑 How to Test:\n";
echo "1. Start server: php -S localhost:8000 -t public/\n";
echo "2. Go to login page\n";
echo "3. Use admin@test.com / admin123 for admin access\n";
echo "4. Use instructor@test.com / instructor123 for instructor access\n";
echo "5. Test the course lifecycle features\n\n";

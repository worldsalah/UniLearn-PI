<?php

require_once 'vendor/autoload.php';

use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Security\Core\Encoder\PasswordHasherFactory;

// Bootstrap Symfony
$container = new ContainerBuilder();
$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/config'));
$loader->load('services.yaml');

// Get entity manager
$entityManager = require_once __DIR__.'/src/Kernel.php';
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

// Create or get user role
$roleRepository = $entityManager->getRepository(Role::class);
$userRole = $roleRepository->findOneBy(['name' => 'user']);

if (!$userRole) {
    $userRole = new Role();
    $userRole->setName('user');
    $entityManager->persist($userRole);
    $entityManager->flush();
    echo "Created 'user' role\n";
}

// Create test user
$userRepository = $entityManager->getRepository(User::class);
$existingUser = $userRepository->findOneBy(['email' => 'test@unilearn.com']);

if ($existingUser) {
    echo "User test@unilearn.com already exists\n";
} else {
    $user = new User();
    $user->setFullName('Test User');
    $user->setEmail('test@unilearn.com');
    $user->setPassword('$2y$10$wlA/iO7WGWRE8k2jpZSnPeE15pqU8VRmPKqJVWIw0Zw8eUHVodbKy'); // malek123+
    $user->setRole($userRole);
    $user->setAgreeTerms(true);
    $user->setStatus('active');
    $user->setBio('Test user for marketplace development');
    $user->setLocation('Tunisia');
    
    $entityManager->persist($user);
    $entityManager->flush();
    
    echo "Created test user successfully!\n";
    echo "Email: test@unilearn.com\n";
    echo "Password: malek123+\n";
}

echo "\nLogin Credentials:\n";
echo "==================\n";
echo "Email: test@unilearn.com\n";
echo "Password: malek123+\n";

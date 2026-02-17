<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create admin user'
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Check if admin user already exists
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@unilearn.com']);
        
        if ($existingAdmin) {
            // Update password for existing admin
            $hashedPassword = $this->passwordHasher->hashPassword($existingAdmin, 'admin123');
            $existingAdmin->setPassword($hashedPassword);
            $output->writeln('<info>Admin user already exists. Password updated.</info>');
        } else {
            // Create new admin user
            $admin = new User();
            $admin->setName('Admin User');
            $admin->setEmail('admin@unilearn.com');
            // Set role as entity
            $adminRole = $this->entityManager->getRepository(\App\Entity\Role::class)->findOneBy(['name' => 'admin']);
            $admin->setRole($adminRole);
            
            $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
            $admin->setPassword($hashedPassword);
            
            $this->entityManager->persist($admin);
            $output->writeln('<info>Admin user created successfully.</info>');
        }

        $this->entityManager->flush();
        
        $output->writeln('<info>Admin login: admin@unilearn.com / admin123</info>');

        return Command::SUCCESS;
    }
}

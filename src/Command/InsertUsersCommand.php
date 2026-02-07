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
    name: 'app:insert-users',
    description: 'Insert sample users into the database'
)]
class InsertUsersCommand extends Command
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
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@unilearn.com', 'role' => 'admin', 'password' => 'admin123'],
            ['name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'teacher', 'password' => 'teacher123'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'student', 'password' => 'student123'],
            ['name' => 'Bob Wilson', 'email' => 'bob@example.com', 'role' => 'teacher', 'password' => 'teacher123'],
            ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'role' => 'student', 'password' => 'student123'],
            ['name' => 'Mike Brown', 'email' => 'mike@example.com', 'role' => 'teacher', 'password' => 'teacher123'],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setName($userData['name']);
            $user->setEmail($userData['email']);
            $user->setRole($userData['role']);
            
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
            
            $this->entityManager->persist($user);
            
            $output->writeln("Created user: {$userData['name']} ({$userData['email']}) with role: {$userData['role']}");
        }

        $this->entityManager->flush();
        
        $output->writeln('<info>Successfully inserted ' . count($users) . ' users into the database.</info>');
        $output->writeln('<info>Admin login: admin@unilearn.com / admin123</info>');

        return Command::SUCCESS;
    }
}

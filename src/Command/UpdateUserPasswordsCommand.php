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
    name: 'app:update-passwords',
    description: 'Update existing users with passwords'
)]
class UpdateUserPasswordsCommand extends Command
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
        $users = $this->entityManager->getRepository(User::class)->findAll();

        $passwordMap = [
            'admin@unilearn.com' => 'admin123',
            'john@example.com' => 'teacher123',
            'jane@example.com' => 'student123',
            'bob@example.com' => 'teacher123',
            'alice@example.com' => 'student123',
            'mike@example.com' => 'teacher123',
        ];

        foreach ($users as $user) {
            $email = $user->getEmail();
            if (isset($passwordMap[$email])) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $passwordMap[$email]);
                $user->setPassword($hashedPassword);
                $output->writeln("Updated password for: {$email}");
            }
        }

        $this->entityManager->flush();

        $output->writeln('<info>Successfully updated user passwords.</info>');
        $output->writeln('<info>Admin login: admin@unilearn.com / admin123</info>');

        return Command::SUCCESS;
    }
}

<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:associate-courses',
    description: 'Associate courses with users'
)]
class AssociateCoursesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get all users and courses
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $courses = $this->entityManager->getRepository(Course::class)->findAll();

        if (empty($users)) {
            $output->writeln('<error>No users found. Please run app:insert-users first.</error>');
            return Command::FAILURE;
        }

        if (empty($courses)) {
            $output->writeln('<error>No courses found.</error>');
            return Command::FAILURE;
        }

        // Associate courses with teachers
        $teachers = array_filter($users, fn($user) => $user->getRole() === 'teacher');
        $teacherArray = array_values($teachers);

        foreach ($courses as $index => $course) {
            if (empty($teacherArray)) {
                break;
            }
            
            // Assign course to a teacher (round-robin)
            $teacherIndex = $index % count($teacherArray);
            $teacher = $teacherArray[$teacherIndex];
            
            $course->setUser($teacher);
            $this->entityManager->persist($course);
            
            $output->writeln("Associated course '{$course->getTitle()}' with teacher '{$teacher->getName()}'");
        }

        $this->entityManager->flush();
        
        $output->writeln('<info>Successfully associated courses with users.</info>');

        return Command::SUCCESS;
    }
}

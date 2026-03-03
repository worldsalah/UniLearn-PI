<?php

namespace App\Command;

use App\Entity\Enrollment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recalculate-income',
    description: 'Recalculate instructor income from all enrollments',
)]
class RecalculateIncomeCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Recalculating Instructor Income from Enrollments');
        
        // Get all enrollments
        $enrollmentRepository = $this->entityManager->getRepository(Enrollment::class);
        $userRepository = $this->entityManager->getRepository(User::class);
        
        $enrollments = $enrollmentRepository->findAll();
        
        // Reset all instructor incomes to 0
        $users = $userRepository->findAll();
        $instructorIncomes = [];
        
        foreach ($users as $user) {
            $user->setIncome(0);
        }
        
        $this->entityManager->flush();
        
        // Calculate income from enrollments
        $totalEnrollments = 0;
        $totalIncome = 0;
        
        foreach ($enrollments as $enrollment) {
            $course = $enrollment->getCourse();
            
            if ($course === null) {
                continue;
            }
            
            $instructor = $course->getUser();
            
            if ($instructor !== null) {
                $price = $course->getPrice();
                if ($price !== null) {
                    $instructor->addIncome($price);
                }
                
                $instructorId = $instructor->getId();
                if (!isset($instructorIncomes[$instructorId])) {
                    $instructorIncomes[$instructorId] = [
                        'name' => $instructor->getFullName(),
                        'income' => 0,
                        'enrollments' => 0
                    ];
                }
                $instructorIncomes[$instructorId]['income'] += $price;
                $instructorIncomes[$instructorId]['enrollments']++;
                
                $totalIncome += $price;
                $totalEnrollments++;
            }
        }
        
        $this->entityManager->flush();
        
        // Display results
        $io->section('Income Summary by Instructor:');
        
        $tableRows = [];
        foreach ($instructorIncomes as $data) {
            $tableRows[] = [$data['name'], $data['enrollments'], '$' . number_format($data['income'], 2)];
        }
        
        if (!empty($tableRows)) {
            $io->table(
                ['Instructor', 'Enrollments', 'Total Income'],
                $tableRows
            );
        }
        
        $io->success([
            "Recalculation complete!",
            "Total Enrollments: $totalEnrollments",
            "Total Income Distributed: $" . number_format($totalIncome, 2)
        ]);
        
        return Command::SUCCESS;
    }
}

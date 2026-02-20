<?php

namespace App\Command;

use App\Entity\QuizResult;
use App\Entity\User;
use App\Entity\Quiz;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:insert-quiz-results',
    description: 'Insert sample quiz results data',
)]
class InsertQuizResultsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $quizRepository = $this->entityManager->getRepository(Quiz::class);

        $users = $userRepository->findAll();
        $quizzes = $quizRepository->findAll();

        if (empty($users)) {
            $output->writeln('<error>No users found. Please create users first.</error>');
            return Command::FAILURE;
        }

        if (empty($quizzes)) {
            $output->writeln('<error>No quizzes found. Please create quizzes first.</error>');
            return Command::FAILURE;
        }

        // Sample quiz results data
        $sampleResults = [
            ['score' => 85, 'maxScore' => 100, 'daysAgo' => 0],
            ['score' => 92, 'maxScore' => 100, 'daysAgo' => 2],
            ['score' => 78, 'maxScore' => 100, 'daysAgo' => 5],
            ['score' => 95, 'maxScore' => 100, 'daysAgo' => 7],
            ['score' => 88, 'maxScore' => 100, 'daysAgo' => 10],
            ['score' => 72, 'maxScore' => 100, 'daysAgo' => 12],
            ['score' => 90, 'maxScore' => 100, 'daysAgo' => 15],
            ['score' => 83, 'maxScore' => 100, 'daysAgo' => 18],
            ['score' => 96, 'maxScore' => 100, 'daysAgo' => 20],
            ['score' => 79, 'maxScore' => 100, 'daysAgo' => 22],
        ];

        $insertedCount = 0;

        foreach ($sampleResults as $resultData) {
            // Random user and quiz
            $user = $users[array_rand($users)];
            $quiz = $quizzes[array_rand($quizzes)];

            // Create QuizResult
            $quizResult = new QuizResult();
            $quizResult->setUser($user);
            $quizResult->setQuiz($quiz);
            $quizResult->setScore($resultData['score']);
            $quizResult->setMaxScore($resultData['maxScore']);
            
            // Set takenAt date (days ago)
            $takenAt = new \DateTimeImmutable();
            $takenAt = $takenAt->modify("-{$resultData['daysAgo']} days");
            $quizResult->setTakenAt($takenAt);

            $this->entityManager->persist($quizResult);
            $insertedCount++;
        }

        $this->entityManager->flush();

        $output->writeln("<info>Successfully inserted {$insertedCount} quiz results!</info>");

        // Display summary
        $quizResults = $this->entityManager->getRepository(QuizResult::class)->findAll();
        $output->writeln("\n<info>Current Quiz Results Summary:</info>");
        $output->writeln(str_repeat("=", 80));
        
        $tableData = [];
        foreach (array_slice($quizResults, -10) as $result) {
            $tableData[] = [
                $result->getId(),
                substr($result->getUser()->getEmail(), 0, 20),
                substr($result->getQuiz()->getTitle() ?? 'N/A', 0, 20),
                $result->getScore(),
                $result->getMaxScore(),
                round($result->getPercentage(), 1) . '%',
                $result->getTakenAt()->format('Y-m-d')
            ];
        }

        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table->setHeaders(['ID', 'User', 'Quiz', 'Score', 'Max', 'Percentage', 'Date']);
        $table->setRows($tableData);
        $table->render();

        return Command::SUCCESS;
    }
}

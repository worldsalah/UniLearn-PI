<?php

require_once 'vendor/autoload.php';

use App\Entity\QuizResult;
use App\Entity\User;
use App\Entity\Quiz;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

// Bootstrap Symfony
$kernel = new Kernel('dev', true);
$kernel->boot();

// Get EntityManager
$entityManager = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');

// Get repositories
$userRepository = $entityManager->getRepository(User::class);
$quizRepository = $entityManager->getRepository(Quiz::class);

try {
    // Get sample data
    $users = $userRepository->findAll();
    $quizzes = $quizRepository->findAll();

    if (empty($users)) {
        echo "No users found. Please create users first.\n";
        exit(1);
    }

    if (empty($quizzes)) {
        echo "No quizzes found. Please create quizzes first.\n";
        exit(1);
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

        $entityManager->persist($quizResult);
        $insertedCount++;
    }

    $entityManager->flush();

    echo "Successfully inserted {$insertedCount} quiz results!\n";

    // Display inserted results
    $quizResults = $entityManager->getRepository(QuizResult::class)->findAll();
    echo "\nCurrent Quiz Results:\n";
    echo str_repeat("=", 80) . "\n";
    printf("%-5s %-20s %-20s %-10s %-10s %-15s\n", 
        "ID", "User", "Quiz", "Score", "Max", "Date");
    echo str_repeat("-", 80) . "\n";

    foreach ($quizResults as $result) {
        printf("%-5d %-20s %-20s %-10d %-10d %-15s\n",
            $result->getId(),
            substr($result->getUser()->getEmail(), 0, 20),
            substr($result->getQuiz()->getTitle() ?? 'N/A', 0, 20),
            $result->getScore(),
            $result->getMaxScore(),
            $result->getTakenAt()->format('Y-m-d')
        );
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

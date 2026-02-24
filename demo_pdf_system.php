<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use App\Entity\Quiz;
use App\Entity\QuizResult;
use App\Entity\QuizAttempt;
use Doctrine\ORM\EntityManagerInterface;

echo "🎯 Smart Exam Report PDF System Demo\n\n";

// Initialize Symfony kernel
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $entityManager */
$entityManager = $container->get('doctrine.orm.entity_manager');

try {
    echo "📊 Creating sample data...\n";
    
    // Check if we have existing data
    $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => 'demo@student.com']);
    $existingQuiz = $entityManager->getRepository(Quiz::class)->findOneBy(['title' => 'Demo Quiz']);
    
    if (!$existingUser) {
        // Create sample user
        $user = new User();
        $user->setEmail('demo@student.com');
        $user->setFullName('Demo Student');
        $user->setPassword('demo123');
        $entityManager->persist($user);
        echo "✅ Created demo user\n";
    } else {
        $user = $existingUser;
        echo "✅ Using existing demo user\n";
    }
    
    if (!$existingQuiz) {
        // Create sample quiz
        $quiz = new Quiz();
        $quiz->setTitle('Demo Quiz - Mathematics');
        $quiz->setDuration(60); // 60 minutes
        $entityManager->persist($quiz);
        echo "✅ Created demo quiz\n";
    } else {
        $quiz = $existingQuiz;
        echo "✅ Using existing demo quiz\n";
    }
    
    // Create sample quiz result
    $existingResult = $entityManager->getRepository(QuizResult::class)
        ->findOneBy(['user' => $user, 'quiz' => $quiz]);
    
    if (!$existingResult) {
        $quizResult = new QuizResult();
        $quizResult->setUser($user);
        $quizResult->setQuiz($quiz);
        $quizResult->setScore(85);
        $quizResult->setMaxScore(100);
        $quizResult->setTakenAt(new \DateTimeImmutable());
        $entityManager->persist($quizResult);
        echo "✅ Created quiz result (85/100)\n";
    } else {
        $quizResult = $existingResult;
        echo "✅ Using existing quiz result\n";
    }
    
    // Create sample quiz attempts for progress tracking
    $attempts = $entityManager->getRepository(QuizAttempt::class)
        ->findBy(['user' => $user, 'quiz' => $quiz], ['startedAt' => 'ASC']);
    
    if (empty($attempts)) {
        for ($i = 1; $i <= 3; $i++) {
            $attempt = new QuizAttempt();
            $attempt->setUser($user);
            $attempt->setQuiz($quiz);
            $attempt->setScore(70 + ($i * 5)); // Progress: 75, 80, 85
            $attempt->setTotalQuestions(20);
            $attempt->setCorrectAnswers(15 + $i);
            $attempt->setStartedAt(new \DateTime("-" . (4 - $i) . " days"));
            $attempt->setCompletedAt(new \DateTime("-" . (4 - $i) . " days"));
            $attempt->setStatus('completed');
            $entityManager->persist($attempt);
        }
        echo "✅ Created 3 quiz attempts for progress tracking\n";
    } else {
        echo "✅ Using existing quiz attempts\n";
    }
    
    $entityManager->flush();
    
    echo "\n🔗 Access URLs:\n";
    echo "PDF Report: http://localhost/UniLearn-PI-main123/public/advanced-pdf/generate/{$quizResult->getId()}\n";
    echo "Alternative: http://localhost/UniLearn-PI-main123/public/quiz/pdf/{$quizResult->getId()}\n";
    
    // Generate verification URL
    $token = md5($quizResult->getId() . $quizResult->getCreatedAt()->format('Y-m-d H:i:s'));
    echo "Verification: http://localhost/UniLearn-PI-main123/public/verification/quiz/{$quizResult->getId()}/{$token}\n";
    
    echo "\n📈 Sample Analysis Results:\n";
    echo "Score: {$quizResult->getScore()}/{$quizResult->getMaxScore()} ({$quizResult->getPercentage()}%)\n";
    echo "Grade: " . ($quizResult->getPercentage() >= 80 ? 'Excellent' : 'Bien') . "\n";
    echo "Date: " . $quizResult->getTakenAt()->format('d/m/Y H:i') . "\n";
    
    echo "\n🎨 PDF Features Included:\n";
    echo "✅ Professional cover page with branding\n";
    echo "✅ Detailed performance analysis\n";
    echo "✅ Charts and visualizations\n";
    echo "✅ Personalized recommendations\n";
    echo "✅ Class comparison metrics\n";
    echo "✅ Progress tracking over time\n";
    echo "✅ Strengths and weaknesses analysis\n";
    echo "✅ QR code for verification\n";
    echo "✅ Study suggestions\n";
    echo "✅ Time management analysis\n";
    
    echo "\n🚀 Your Smart Exam Report PDF System is ready to use!\n";
    echo "📱 Open the URLs above in your browser to see the system in action.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Make sure your database is running and configured correctly.\n";
} finally {
    $kernel->shutdown();
}

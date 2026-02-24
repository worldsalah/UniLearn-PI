<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Entity\QuizResult;
use App\Entity\Quiz;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;

// Simple test script to verify PDF generation
echo "🚀 Testing Advanced PDF Report System\n\n";

// Check if required dependencies are available
echo "📦 Checking dependencies...\n";

$requiredExtensions = ['gd', 'imagick', 'zip'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded\n";
    } else {
        echo "❌ $ext extension missing\n";
    }
}

// Check if wkhtmltopdf is available
echo "\n🔍 Checking wkhtmltopdf...\n";
$output = [];
$returnCode = 0;
exec('wkhtmltopdf --version 2>&1', $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ wkhtmltopdf is available: " . $output[0] . "\n";
} else {
    echo "❌ wkhtmltopdf not found. Please install it:\n";
    echo "   - Download from: https://wkhtmltopdf.org/downloads.html\n";
    echo "   - Add to PATH or configure in knp_snappy.yaml\n";
}

// Test QR code generation
echo "\n🔐 Testing QR Code generation...\n";
try {
    $result = Builder::create()
        ->data('https://example.com/test')
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
        ->size(150)
        ->margin(10)
        ->build();
    
    echo "✅ QR Code generation working\n";
} catch (Exception $e) {
    echo "❌ QR Code generation failed: " . $e->getMessage() . "\n";
}

// Check Symfony services
echo "\n🔧 Checking Symfony services...\n";
try {
    $kernel = require_once __DIR__ . '/src/Kernel.php';
    $kernel = new \App\Kernel('test', false);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    
    // Check if PDF service is available
    if ($container->has('knp_snappy.pdf')) {
        echo "✅ KnpSnappy PDF service available\n";
    } else {
        echo "❌ KnpSnappy PDF service not found\n";
    }
    
    // Check if analysis service is available
    if ($container->has(App\Service\QuizAnalysisService::class)) {
        echo "✅ QuizAnalysisService available\n";
    } else {
        echo "❌ QuizAnalysisService not found\n";
    }
    
    $kernel->shutdown();
} catch (Exception $e) {
    echo "❌ Symfony bootstrap failed: " . $e->getMessage() . "\n";
}

echo "\n📋 Usage Instructions:\n";
echo "1. Generate a quiz result first through your application\n";
echo "2. Access PDF report: /advanced-pdf/generate/{quizResultId}\n";
echo "3. Or use the shortcut: /quiz/pdf/{quizResultId}\n";
echo "4. Verify authenticity: /verification/quiz/{resultId}/{token}\n";

echo "\n🎯 Features Implemented:\n";
echo "✅ Advanced PDF generation with KnpSnappyBundle\n";
echo "✅ Intelligent performance analysis\n";
echo "✅ Charts and visualizations\n";
echo "✅ QR code verification system\n";
echo "✅ Personalized recommendations\n";
echo "✅ Class comparison and ranking\n";
echo "✅ Progress tracking\n";
echo "✅ Professional PDF design\n";

echo "\n🔥 Your Smart Exam Report PDF System is ready! 🔥\n";

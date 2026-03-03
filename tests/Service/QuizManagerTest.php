<?php

namespace App\Tests\Service;

use App\Entity\Quiz;
use App\Entity\QuizSettings;
use App\Service\QuizManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour QuizManager
 * Règles métier validées:
 * 1. Le titre est obligatoire
 * 2. Le score de passage doit être entre 0 et 100
 * 3. Le temps limite doit être positif si défini
 */
class QuizManagerTest extends TestCase
{
    public function testValidQuiz(): void
    {
        $quiz = new Quiz();
        $quiz->setTitle('Symfony Quiz');
        
        $manager = new QuizManager();
        $this->assertTrue($manager->validate($quiz));
    }

    public function testQuizWithoutTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre du quiz est obligatoire');
        
        $quiz = new Quiz();
        
        $manager = new QuizManager();
        $manager->validate($quiz);
    }

    public function testValidQuizSettings(): void
    {
        $settings = new QuizSettings();
        $settings->setPassingScore(70);
        $settings->setTimeLimit(30);
        
        $manager = new QuizManager();
        $this->assertTrue($manager->validateSettings($settings));
    }

    public function testQuizSettingsWithInvalidPassingScore(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le score de passage doit être entre 0 et 100');
        
        $settings = new QuizSettings();
        $settings->setPassingScore(150);
        
        $manager = new QuizManager();
        $manager->validateSettings($settings);
    }

    public function testQuizSettingsWithNegativePassingScore(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le score de passage doit être entre 0 et 100');
        
        $settings = new QuizSettings();
        $settings->setPassingScore(-10);
        
        $manager = new QuizManager();
        $manager->validateSettings($settings);
    }

    public function testQuizSettingsWithNegativeTimeLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le temps limite doit être positif');
        
        $settings = new QuizSettings();
        $settings->setTimeLimit(-5);
        
        $manager = new QuizManager();
        $manager->validateSettings($settings);
    }

    public function testCalculateScore(): void
    {
        $manager = new QuizManager();
        $score = $manager->calculateScore(8, 10);
        
        $this->assertEquals(80.0, $score);
    }

    public function testCalculateScoreWithZeroQuestions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre total de questions doit être positif');
        
        $manager = new QuizManager();
        $manager->calculateScore(5, 0);
    }

    public function testHasPassed(): void
    {
        $settings = new QuizSettings();
        $settings->setPassingScore(70);
        
        $manager = new QuizManager();
        
        $this->assertTrue($manager->hasPassed($settings, 80));
        $this->assertFalse($manager->hasPassed($settings, 60));
    }

    public function testHasPassedWithDefaultScore(): void
    {
        $settings = new QuizSettings();
        
        $manager = new QuizManager();
        
        $this->assertTrue($manager->hasPassed($settings, 80));
        $this->assertFalse($manager->hasPassed($settings, 60));
    }
}

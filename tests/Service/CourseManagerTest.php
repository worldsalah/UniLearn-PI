<?php

namespace App\Tests\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Service\CourseManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour CourseManager
 * Règles métier validées:
 * 1. Le titre du cours est obligatoire
 * 2. Le prix doit être positif
 * 3. La description courte doit avoir au moins 20 caractères
 */
class CourseManagerTest extends TestCase
{
    public function testValidCourse(): void
    {
        $course = new Course();
        $course->setTitle('Symfony Advanced Course');
        $course->setPrice(99.99);
        $course->setShortDescription('This is a comprehensive course about Symfony framework development');
        
        $manager = new CourseManager();
        $this->assertTrue($manager->validate($course));
    }

    public function testCourseWithoutTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre du cours est obligatoire');
        
        $course = new Course();
        $course->setPrice(99.99);
        $course->setShortDescription('This is a valid description');
        
        $manager = new CourseManager();
        $manager->validate($course);
    }

    public function testCourseWithNegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix doit être un nombre positif');
        
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setPrice(-10);
        $course->setShortDescription('This is a valid description');
        
        $manager = new CourseManager();
        $manager->validate($course);
    }

    public function testCourseWithoutShortDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description courte est obligatoire');
        
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setPrice(50);
        
        $manager = new CourseManager();
        $manager->validate($course);
    }

    public function testCourseWithTooShortDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description courte doit contenir au moins 20 caractères');
        
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setPrice(50);
        $course->setShortDescription('Too short');
        
        $manager = new CourseManager();
        $manager->validate($course);
    }

    public function testCanEnrollWithSufficientBalance(): void
    {
        $course = new Course();
        $course->setPrice(50);
        
        $user = new User();
        $user->setIncome(100);
        
        $manager = new CourseManager();
        $this->assertTrue($manager->canEnroll($course, $user));
    }

    public function testCannotEnrollWithInsufficientBalance(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Solde insuffisant');
        
        $course = new Course();
        $course->setPrice(100);
        
        $user = new User();
        $user->setIncome(50);
        
        $manager = new CourseManager();
        $manager->canEnroll($course, $user);
    }
}

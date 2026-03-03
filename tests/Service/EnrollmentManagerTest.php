<?php

namespace App\Tests\Service;

use App\Entity\Enrollment;
use App\Entity\User;
use App\Entity\Course;
use App\Service\EnrollmentManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour EnrollmentManager
 * Règles métier validées:
 * 1. L'utilisateur est obligatoire
 * 2. Le cours est obligatoire
 * 3. L'utilisateur doit être actif pour s'inscrire
 */
class EnrollmentManagerTest extends TestCase
{
    public function testValidEnrollment(): void
    {
        $user = new User();
        $user->setFullName('Test User');
        $user->setStatus('active');
        
        $course = new Course();
        $course->setTitle('Test Course');
        
        $enrollment = new Enrollment();
        $enrollment->setUser($user);
        $enrollment->setCourse($course);
        
        $manager = new EnrollmentManager();
        $this->assertTrue($manager->validate($enrollment));
    }

    public function testEnrollmentWithoutUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'utilisateur est obligatoire');
        
        $course = new Course();
        $course->setTitle('Test Course');
        
        $enrollment = new Enrollment();
        $enrollment->setCourse($course);
        
        $manager = new EnrollmentManager();
        $manager->validate($enrollment);
    }

    public function testEnrollmentWithoutCourse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le cours est obligatoire');
        
        $user = new User();
        $user->setFullName('Test User');
        
        $enrollment = new Enrollment();
        $enrollment->setUser($user);
        
        $manager = new EnrollmentManager();
        $manager->validate($enrollment);
    }

    public function testCanEnrollActiveUser(): void
    {
        $user = new User();
        $user->setStatus('active');
        
        $course = new Course();
        $course->setTitle('Test Course');
        
        $manager = new EnrollmentManager();
        $this->assertTrue($manager->canEnroll($user, $course));
    }

    public function testCannotEnrollInactiveUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'utilisateur doit être actif pour s\'inscrire');
        
        $user = new User();
        $user->setStatus('inactive');
        
        $course = new Course();
        $course->setTitle('Test Course');
        
        $manager = new EnrollmentManager();
        $manager->canEnroll($user, $course);
    }

    public function testCalculateProgress(): void
    {
        $manager = new EnrollmentManager();
        
        $this->assertEquals(50.0, $manager->calculateProgress(5, 10));
        $this->assertEquals(100.0, $manager->calculateProgress(10, 10));
        $this->assertEquals(0.0, $manager->calculateProgress(0, 10));
    }

    public function testCalculateProgressWithZeroTotal(): void
    {
        $manager = new EnrollmentManager();
        $this->assertEquals(0, $manager->calculateProgress(5, 0));
    }
}

<?php

namespace App\Tests\Unit;

use App\Entity\Course;
use App\Entity\User;
use App\Enum\CourseStatus;
use App\Repository\CourseRepository;
use App\Service\CourseLifecycleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CourseLifecycleServiceTest extends TestCase
{
    private $entityManager;
    private $security;
    private $validator;
    private $eventDispatcher;
    private $courseRepository;
    private CourseLifecycleService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->courseRepository = $this->createMock(CourseRepository::class);

        $this->service = new CourseLifecycleService(
            $this->entityManager,
            $this->security,
            $this->validator,
            $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class),
            $this->eventDispatcher,
            $this->courseRepository
        );
    }

    public function testCanTransitionToValidTransition(): void
    {
        $course = new Course();
        $course->setStatus(CourseStatus::DRAFT->value);

        $this->assertTrue(
            $this->service->canTransitionTo($course, CourseStatus::IN_REVIEW)
        );
    }

    public function testCanTransitionToInvalidTransition(): void
    {
        $course = new Course();
        $course->setStatus(CourseStatus::DRAFT->value);

        $this->assertFalse(
            $this->service->canTransitionTo($course, CourseStatus::PUBLISHED)
        );
    }

    public function testValidateCourseForSubmissionValidCourse(): void
    {
        $course = $this->createValidCourse();
        $errors = $this->service->validateCourseForSubmission($course);
        
        $this->assertEmpty($errors);
    }

    public function testValidateCourseForSubmissionInvalidCourse(): void
    {
        $course = new Course();
        $course->setTitle('Test'); // Too short
        $course->setShortDescription('Short'); // Too short
        // Missing other required fields
        
        $errors = $this->service->validateCourseForSubmission($course);
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Short description must be at least 20 characters', $errors);
    }

    public function testValidateCourseForSubmissionMinimumLessons(): void
    {
        $course = $this->createValidCourse();
        // Mock getTotalLessons to return less than 3
        $course = $this->createMock(Course::class);
        $course->method('getTotalLessons')->willReturn(2);
        
        $errors = $this->service->validateCourseForSubmission($course);
        
        $this->assertContains('Course must have at least 3 lessons', $errors);
    }

    public function testValidateCourseForSubmissionMinimumDuration(): void
    {
        $course = $this->createValidCourse();
        $course->setDuration(0.3); // Less than 0.5 hours
        
        $errors = $this->service->validateCourseForSubmission($course);
        
        $this->assertContains('Course duration must be at least 30 minutes', $errors);
    }

    private function createValidCourse(): Course
    {
        $course = new Course();
        $course->setTitle('Valid Course Title');
        $course->setShortDescription('This is a valid short description that meets minimum requirements');
        $course->setRequirements('Course requirements');
        $course->setLearningOutcomes('Learning outcomes');
        $course->setTargetAudience('Target audience');
        $course->setThumbnailUrl('/path/to/thumbnail.jpg');
        $course->setDuration(2.0);
        $course->setPrice(99.99);
        
        // Mock chapters and lessons
        $course = $this->createMock(Course::class);
        $course->method('getTitle')->willReturn('Valid Course Title');
        $course->method('getShortDescription')->willReturn('This is a valid short description that meets minimum requirements');
        $course->method('getRequirements')->willReturn('Course requirements');
        $course->method('getLearningOutcomes')->willReturn('Learning outcomes');
        $course->method('getTargetAudience')->willReturn('Target audience');
        $course->method('getThumbnailUrl')->willReturn('/path/to/thumbnail.jpg');
        $course->method('getDuration')->willReturn(2.0);
        $course->method('getTotalLessons')->willReturn(5);
        $course->method('getChapters')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());
        
        return $course;
    }
}

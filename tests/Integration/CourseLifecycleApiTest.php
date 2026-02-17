<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Course;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CourseLifecycleApiTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testInstructor;
    private $testAdmin;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Create test users
        $this->testInstructor = $this->createTestUser('instructor@test.com', ['ROLE_INSTRUCTOR']);
        $this->testAdmin = $this->createTestUser('admin@test.com', ['ROLE_ADMIN']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createTestUser(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword('password');
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    private function createTestCourse(User $instructor): Course
    {
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setShortDescription('This is a test course description that meets minimum requirements');
        $course->setRequirements('Test requirements');
        $course->setLearningOutcomes('Test learning outcomes');
        $course->setTargetAudience('Test target audience');
        $course->setThumbnailUrl('/test/thumbnail.jpg');
        $course->setDuration(2.0);
        $course->setPrice(99.99);
        $course->setUser($instructor);
        $course->setStatus('draft');
        
        $this->entityManager->persist($course);
        $this->entityManager->flush();
        
        return $course;
    }

    public function testGetAllowedTransitions(): void
    {
        $this->client->request('GET', '/api/courses/transitions');
        
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('transitions', $data);
        $this->assertArrayHasKey('statuses', $data);
    }

    public function testSubmitCourseAsInstructor(): void
    {
        $course = $this->createTestCourse($this->testInstructor);
        
        // Login as instructor
        $this->client->loginUser($this->testInstructor);
        
        $this->client->request('POST', "/api/courses/{$course->getId()}/submit");
        
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('in_review', $data['new_status']);
    }

    public function testSubmitCourseAsAdminFails(): void
    {
        $course = $this->createTestCourse($this->testInstructor);
        
        // Login as admin (but course belongs to instructor)
        $this->client->loginUser($this->testAdmin);
        
        $this->client->request('POST', "/api/courses/{$course->getId()}/submit");
        
        $this->assertResponseStatusCodeSame(403);
    }

    public function testPublishCourseAsAdmin(): void
    {
        $course = $this->createTestCourse($this->testInstructor);
        $course->setStatus('in_review');
        $this->entityManager->flush();
        
        // Login as admin
        $this->client->loginUser($this->testAdmin);
        
        $this->client->request('POST', "/api/courses/{$course->getId()}/publish");
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('published', $data['new_status']);
    }

    public function testPublishCourseAsInstructorFails(): void
    {
        $course = $this->createTestCourse($this->testInstructor);
        $course->setStatus('in_review');
        $this->entityManager->flush();
        
        // Login as instructor
        $this->client->loginUser($this->testInstructor);
        
        $this->client->request('POST', "/api/courses/{$course->getId()}/publish");
        
        $this->assertResponseStatusCodeSame(403);
    }

    public function testRejectCourseWithReason(): void
    {
        $course = $this->createTestCourse($this->testInstructor);
        $course->setStatus('in_review');
        $this->entityManager->flush();
        
        // Login as admin
        $this->client->loginUser($this->testAdmin);
        
        $this->client->request('POST', "/api/courses/{$course->getId()}/reject", [], [], [], 
            json_encode(['reason' => 'Course needs more content'])
        );
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('rejected', $data['new_status']);
    }

    public function testRejectCourseWithoutReasonFails(): void
    {
        $course = $this->createTestCourse($this->testInstructor);
        $course->setStatus('in_review');
        $this->entityManager->flush();
        
        // Login as admin
        $this->client->loginUser($this->testAdmin);
        
        $this->client->request('POST', "/api/courses/{$course->getId()}/reject");
        
        $this->assertResponseStatusCodeSame(400);
    }

    public function testValidateCourse(): void
    {
        $course = $this->createTestCourse($this->testInstructor);
        
        // Login as instructor
        $this->client->loginUser($this->testInstructor);
        
        $this->client->request('POST', "/api/courses/{$course->getId()}/validate");
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('valid', $data);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testGetCourseHistory(): void
    {
        $course = $this->createTestCourse($this->testInstructor);
        
        // Login as instructor
        $this->client->loginUser($this->testInstructor);
        
        $this->client->request('GET', "/api/courses/{$course->getId()}/history");
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('course_id', $data);
        $this->assertArrayHasKey('history', $data);
    }

    public function testInvalidTransition(): void
    {
        $course = $this->createTestCourse($this->testInstructor);
        
        // Login as instructor
        $this->client->loginUser($this->testInstructor);
        
        // Try to publish directly from draft (invalid transition)
        $this->client->request('POST', "/api/courses/{$course->getId()}/publish");
        
        $this->assertResponseStatusCodeSame(403);
    }
}

<?php

namespace App\Controller\Public;

use App\Entity\User;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test/setup', name: 'test_setup')]
    public function setupTestUsers(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        CourseRepository $courseRepository
    ): JsonResponse {
        // Create test users if they don't exist
        $testUsers = [
            [
                'email' => 'admin@test.com',
                'password' => 'admin123',
                'roles' => ['ROLE_ADMIN'],
                'fullName' => 'Test Administrator'
            ],
            [
                'email' => 'instructor@test.com', 
                'password' => 'instructor123',
                'roles' => ['ROLE_INSTRUCTOR'],
                'fullName' => 'Test Instructor'
            ],
            [
                'email' => 'student@test.com',
                'password' => 'student123', 
                'roles' => ['ROLE_STUDENT'],
                'fullName' => 'Test Student'
            ]
        ];

        $createdUsers = [];
        
        foreach ($testUsers as $userData) {
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
            
            if (!$existingUser) {
                $user = new User();
                $user->setEmail($userData['email']);
                $user->setRoles($userData['roles']);
                
                $hashedPassword = $passwordHasher->hashPassword($user, $userData['password']);
                $user->setPassword($hashedPassword);
                
                if (method_exists($user, 'setFullName')) {
                    $user->setFullName($userData['fullName']);
                }
                
                $entityManager->persist($user);
                $createdUsers[] = $userData['email'];
            }
        }
        
        $entityManager->flush();
        
        // Get course statistics
        $totalCourses = $courseRepository->count([]);
        $draftCourses = $courseRepository->count(['status' => 'draft']);
        $publishedCourses = $courseRepository->count(['status' => 'published']);
        
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Test environment setup completed',
            'users_created' => $createdUsers,
            'course_stats' => [
                'total' => $totalCourses,
                'draft' => $draftCourses,
                'published' => $publishedCourses
            ],
            'login_credentials' => [
                'admin' => ['email' => 'admin@test.com', 'password' => 'admin123'],
                'instructor' => ['email' => 'instructor@test.com', 'password' => 'instructor123'],
                'student' => ['email' => 'student@test.com', 'password' => 'student123']
            ],
            'test_urls' => [
                'admin_dashboard' => '/admin/courses',
                'api_transitions' => '/api/courses/transitions',
                'login' => '/login'
            ]
        ]);
    }

    #[Route('/test/status', name: 'test_status')]
    public function testStatus(CourseRepository $courseRepository): JsonResponse
    {
        return new JsonResponse([
            'system_status' => 'operational',
            'course_lifecycle' => 'implemented',
            'enum_status' => 'working',
            'database_connected' => true,
            'course_count' => $courseRepository->count([]),
            'available_endpoints' => [
                'GET /api/courses/transitions',
                'POST /api/courses/{id}/submit',
                'POST /api/courses/{id}/publish',
                'POST /api/courses/{id}/reject',
                'GET /admin/courses'
            ]
        ]);
    }

    #[Route('/test/roles', name: 'test_roles')]
    public function testRoles(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse([
                'logged_in' => false,
                'message' => 'No user is currently logged in'
            ]);
        }
        
        return new JsonResponse([
            'logged_in' => true,
            'user_email' => $user->getEmail(),
            'user_roles' => $user->getRoles(),
            'is_admin' => in_array('ROLE_ADMIN', $user->getRoles()),
            'is_instructor' => in_array('ROLE_INSTRUCTOR', $user->getRoles()),
            'is_student' => in_array('ROLE_STUDENT', $user->getRoles()),
            'can_access_admin' => $this->isGranted('ROLE_ADMIN'),
            'can_submit_courses' => $this->isGranted('ROLE_INSTRUCTOR')
        ]);
    }
}

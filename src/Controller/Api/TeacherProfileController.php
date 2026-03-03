<?php

namespace App\Controller\Api;

use App\Entity\TeacherProfile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/teacher', name: 'api_teacher_')]
class TeacherProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Create or update teacher profile
     * 
     * POST /api/teacher/profile
     */
    #[Route('/profile', name: 'profile_create', methods: ['POST'])]
    public function createProfile(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if profile already exists
        $profile = $this->entityManager->getRepository(TeacherProfile::class)
            ->findOneBy(['user' => $user]);
        
        $data = json_decode($request->getContent(), true);
        
        // Validate required fields
        if (empty($data['subjects']) || !is_array($data['subjects'])) {
            return $this->json([
                'success' => false,
                'error' => 'At least one subject is required'
            ], 400);
        }
        
        if (empty($data['hourlyRate']) || $data['hourlyRate'] <= 0) {
            return $this->json([
                'success' => false,
                'error' => 'Valid hourly rate is required'
            ], 400);
        }
        
        // Create new profile if doesn't exist
        if ($profile === null) {
            $profile = new TeacherProfile();
            $profile->setUser($user);
        }
        
        // Update profile data
        $profile->setSubjects($data['subjects']);
        $profile->setHourlyRate((string) $data['hourlyRate']);
        
        if (isset($data['bio'])) {
            $profile->setBio($data['bio']);
        }
        
        if (isset($data['education'])) {
            $profile->setEducation($data['education']);
        }
        
        if (isset($data['experienceYears'])) {
            $profile->setExperienceYears((int) $data['experienceYears']);
        }
        
        $this->entityManager->persist($profile);
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => $profile->getId() !== null ? 'Profile updated successfully' : 'Profile created successfully',
            'data' => [
                'id' => $profile->getId(),
                'userId' => $user->getId(),
                'subjects' => $profile->getSubjects(),
                'hourlyRate' => $profile->getHourlyRate(),
                'bio' => $profile->getBio(),
                'education' => $profile->getEducation(),
                'experienceYears' => $profile->getExperienceYears(),
                'ratingAvg' => $profile->getRatingAvg(),
                'reviewCount' => $profile->getReviewCount()
            ]
        ]);
    }

    /**
     * Get current teacher's profile
     * 
     * GET /api/teacher/profile
     */
    #[Route('/profile', name: 'profile_get', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $profile = $this->entityManager->getRepository(TeacherProfile::class)
            ->findOneBy(['user' => $user]);
        
        if ($profile === null) {
            return $this->json([
                'success' => false,
                'error' => 'Profile not found'
            ], 404);
        }
        
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $profile->getId(),
                'subjects' => $profile->getSubjects(),
                'hourlyRate' => $profile->getHourlyRate(),
                'bio' => $profile->getBio(),
                'education' => $profile->getEducation(),
                'experienceYears' => $profile->getExperienceYears(),
                'ratingAvg' => $profile->getRatingAvg(),
                'reviewCount' => $profile->getReviewCount(),
                'isVerified' => $profile->isVerified()
            ]
        ]);
    }
}

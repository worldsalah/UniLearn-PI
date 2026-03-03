<?php

namespace App\Controller\Api;

use App\Entity\TeacherProfile;
use App\Entity\User;
use App\Service\Availability\AvailabilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/availability', name: 'api_availability_')]
class AvailabilityController extends AbstractController
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Set teacher availability
     * 
     * POST /api/availability
     * Teacher only
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');
        
        /** @var User $teacher */
        $teacher = $this->getUser();
        $profile = $this->entityManager->getRepository(TeacherProfile::class)->findOneBy(['user' => $teacher]);
        
        // Auto-create profile if doesn't exist
        if ($profile === null) {
            $profile = new TeacherProfile();
            $profile->setUser($teacher);
            $profile->setSubjects([]);
            $profile->setHourlyRate('0');
            $profile->setBio('');
            $profile->setExperienceYears(0);
            $this->entityManager->persist($profile);
            $this->entityManager->flush();
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['availabilities']) || !is_array($data['availabilities'])) {
            return $this->json([
                'success' => false,
                'error' => 'availabilities array is required'
            ], 400);
        }
        
        try {
            $created = $this->availabilityService->setAvailability($profile, $data['availabilities']);
            
            return $this->json([
                'success' => true,
                'data' => [
                    'created' => count($created),
                    'availabilities' => array_map(function ($a) {
                        return [
                            'id' => $a->getId(),
                            'dayOfWeek' => $a->getDayOfWeek(),
                            'dayName' => $a->getDayName(),
                            'startTime' => $a->getStartTime()->format('H:i'),
                            'endTime' => $a->getEndTime()->format('H:i'),
                            'isActive' => $a->isActive()
                        ];
                    }, $created)
                ]
            ], 201);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get my availability schedule
     * 
     * GET /api/availability/my
     * Teacher only
     */
    #[Route('/my', name: 'my', methods: ['GET'])]
    public function mySchedule(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');
        
        /** @var User $teacher */
        $teacher = $this->getUser();
        $profile = $this->entityManager->getRepository(TeacherProfile::class)->findOneBy(['user' => $teacher]);
        
        if ($profile === null) {
            return $this->json([
                'success' => false,
                'error' => 'Teacher profile not found'
            ], 404);
        }
        
        $schedule = $this->availabilityService->getWeeklySchedule($profile);
        
        $data = array_map(function ($a) {
            return [
                'id' => $a->getId(),
                'dayOfWeek' => $a->getDayOfWeek(),
                'dayName' => $a->getDayName(),
                'startTime' => $a->getStartTime()->format('H:i'),
                'endTime' => $a->getEndTime()->format('H:i'),
                'isActive' => $a->isActive(),
                'durationMinutes' => $a->getDurationMinutes()
            ];
        }, $schedule);
        
        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Update availability entry
     * 
     * PUT /api/availability/{id}
     * Teacher only
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');
        
        $availability = $this->entityManager->getRepository(\App\Entity\Availability::class)->find($id);
        
        if ($availability === null) {
            return $this->json([
                'success' => false,
                'error' => 'Availability not found'
            ], 404);
        }
        
        // Check ownership
        /** @var User $teacher */
        $teacher = $this->getUser();
        $teacherProfile = $availability->getTeacher();
        $user = $teacherProfile !== null ? $teacherProfile->getUser() : null;
        if ($user === null || $user->getId() !== $teacher->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'Not authorized'
            ], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        try {
            $availability = $this->availabilityService->updateAvailability($availability, $data);
            
            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $availability->getId(),
                    'dayOfWeek' => $availability->getDayOfWeek(),
                    'startTime' => $availability->getStartTime() !== null ? $availability->getStartTime()->format('H:i') : null,
                    'endTime' => $availability->getEndTime() !== null ? $availability->getEndTime()->format('H:i') : null,
                    'isActive' => $availability->isActive()
                ]
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete availability
     * 
     * DELETE /api/availability/{id}
     * Teacher only
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');
        
        $availability = $this->entityManager->getRepository(\App\Entity\Availability::class)->find($id);
        
        if ($availability === null) {
            return $this->json([
                'success' => false,
                'error' => 'Availability not found'
            ], 404);
        }
        
        // Check ownership
        /** @var User $teacher */
        $teacher = $this->getUser();
        $teacherProfile = $availability->getTeacher();
        $user = $teacherProfile !== null ? $teacherProfile->getUser() : null;
        if ($user === null || $user->getId() !== $teacher->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'Not authorized'
            ], 403);
        }
        
        try {
            $this->availabilityService->deleteAvailability($availability);
            
            return $this->json([
                'success' => true
            ], 204);
            
        } catch (\LogicException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}

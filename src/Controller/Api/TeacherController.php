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

#[Route('/api/teachers', name: 'api_teachers_')]
class TeacherController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvailabilityService $availabilityService
    ) {}

    /**
     * List teachers with filtering
     * 
     * GET /api/teachers
     * Public
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $subject = $request->query->get('subject');
        $minRating = $request->query->get('minRating');
        $maxPrice = $request->query->get('maxPrice');
        $page = (int) $request->query->get('page', 1);
        $limit = min((int) $request->query->get('limit', 10), 50);
        
        $qb = $this->entityManager->getRepository(TeacherProfile::class)
            ->createQueryBuilder('tp')
            ->join('tp.user', 'u')
            ->andWhere('tp.isVerified = true');
        
        // Filter by subject
        if ($subject !== null && $subject !== '') {
            $qb->andWhere('JSON_CONTAINS(tp.subjects, :subject) = 1')
               ->setParameter('subject', json_encode($subject));
        }
        
        // Filter by minimum rating
        if ($minRating !== null) {
            $qb->andWhere('tp.ratingAvg >= :minRating')
               ->setParameter('minRating', (float) $minRating);
        }
        
        // Filter by maximum price
        if ($maxPrice !== null) {
            $qb->andWhere('tp.hourlyRate <= :maxPrice')
               ->setParameter('maxPrice', (float) $maxPrice);
        }
        
        // Get total count
        $totalQb = clone $qb;
        $total = (int) $totalQb->select('COUNT(tp.id)')->getQuery()->getSingleScalarResult();
        
        // Paginate
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit)
           ->orderBy('tp.ratingAvg', 'DESC');
        
        $teachers = $qb->getQuery()->getResult();
        
        $data = array_map(function ($profile) {
            return [
                'id' => $profile->getUser()->getId(),
                'firstName' => $profile->getUser()->getFirstName(),
                'lastName' => $profile->getUser()->getLastName(),
                'subjects' => $profile->getSubjects(),
                'hourlyRate' => $profile->getHourlyRate(),
                'rating' => $profile->getRatingAvg(),
                'reviewCount' => $profile->getReviewCount(),
                'bio' => $profile->getBio(),
                'isVerified' => $profile->isVerified()
            ];
        }, $teachers);
        
        return $this->json([
            'success' => true,
            'data' => [
                'teachers' => $data,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil($total / $limit)
                ]
            ]
        ]);
    }

    /**
     * Get teacher details
     * 
     * GET /api/teachers/{id}
     * Public
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if ($user === null || $user->getTeacherProfile() === null) {
            return $this->json([
                'success' => false,
                'error' => 'Teacher not found'
            ], 404);
        }
        
        $profile = $user->getTeacherProfile();
        
        // Get recent reviews
        $reviews = $this->entityManager->getRepository(\App\Entity\Review::class)
            ->findBy(['teacher' => $user], ['createdAt' => 'DESC'], 5);
        
        $reviewData = array_map(function ($review) {
            $createdAt = $review->getCreatedAt();
            return [
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
                'createdAt' => $createdAt !== null ? $createdAt->format('c') : null
            ];
        }, $reviews);
        
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'subjects' => $profile->getSubjects(),
                'hourlyRate' => $profile->getHourlyRate(),
                'rating' => $profile->getRatingAvg(),
                'reviewCount' => $profile->getReviewCount(),
                'bio' => $profile->getBio(),
                'education' => $profile->getEducation(),
                'experienceYears' => $profile->getExperienceYears(),
                'isVerified' => $profile->isVerified(),
                'recentReviews' => $reviewData
            ]
        ]);
    }

    /**
     * Get teacher availability
     * 
     * GET /api/teachers/{id}/availability
     * Public
     */
    #[Route('/{id}/availability', name: 'availability', methods: ['GET'])]
    public function availability(Request $request, string $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if ($user === null || $user->getTeacherProfile() === null) {
            return $this->json([
                'success' => false,
                'error' => 'Teacher not found'
            ], 404);
        }
        
        $startDateStr = $request->query->get('startDate', 'today');
        $endDateStr = $request->query->get('endDate', '+7 days');
        $startDate = new \DateTime(is_string($startDateStr) ? $startDateStr : 'today');
        $endDate = new \DateTime(is_string($endDateStr) ? $endDateStr : '+7 days');
        
        $slots = $this->availabilityService->getAvailableSlots(
            $user->getTeacherProfile(),
            $startDate,
            $endDate
        );
        
        $slotData = array_map(function ($slot) {
            return [
                'id' => $slot->getId(),
                'date' => $slot->getDate()->format('Y-m-d'),
                'startTime' => $slot->getStartTime()->format('H:i'),
                'endTime' => $slot->getEndTime()->format('H:i'),
                'status' => $slot->getStatus()
            ];
        }, $slots);
        
        return $this->json([
            'success' => true,
            'data' => [
                'teacherId' => $id,
                'slots' => $slotData
            ]
        ]);
    }
}

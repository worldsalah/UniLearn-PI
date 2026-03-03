<?php

namespace App\Controller\Api;

use App\Entity\Review;
use App\Entity\TutoringSession;
use App\Entity\User;
use App\Exception\BusinessRuleViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/reviews', name: 'api_reviews_')]
class ReviewController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Create a review
     * 
     * POST /api/reviews
     * Student only, after completed session
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        
        /** @var User $student */
        $student = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['sessionId']) || empty($data['rating'])) {
            return $this->json([
                'success' => false,
                'error' => 'sessionId and rating are required'
            ], 400);
        }
        
        $session = $this->entityManager->getRepository(TutoringSession::class)->find($data['sessionId']);
        
        if ($session === null) {
            return $this->json([
                'success' => false,
                'error' => 'Session not found'
            ], 404);
        }
        
        // Validate session is completed
        if (!$session->canBeReviewed()) {
            return $this->json([
                'success' => false,
                'error' => 'Session must be completed before reviewing'
            ], 400);
        }
        
        // Validate student owns this session
        $sessionStudent = $session->getStudent();
        if ($sessionStudent === null || $sessionStudent->getId() !== $student->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'Only the booking student can review'
            ], 403);
        }
        
        // Check if already reviewed
        if ($session->hasReview()) {
            return $this->json([
                'success' => false,
                'error' => 'This session has already been reviewed'
            ], 400);
        }
        
        // Create review
        $review = new Review();
        $review->setSession($session);
        $review->setStudent($student);
        $review->setTeacher($session->getTeacher());
        
        try {
            $review->setRating((int) $data['rating']);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Rating must be between 1 and 5'
            ], 400);
        }
        
        $review->setComment($data['comment'] ?? null);
        $review->setIsPublic($data['isPublic'] ?? true);
        
        $this->entityManager->persist($review);
        
        // Update teacher rating
        $teacher = $session->getTeacher();
        $teacherProfile = $teacher !== null ? $teacher->getTeacherProfile() : null;
        if ($teacherProfile !== null) {
            $teacherProfile->updateRating($review->getRating());
        }
        
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $review->getId(),
                'rating' => $review->getRating(),
                'ratingLabel' => $review->getRatingLabel(),
                'comment' => $review->getComment(),
                'createdAt' => $review->getCreatedAt() !== null ? $review->getCreatedAt()->format('c') : null
            ]
        ], 201);
    }

    /**
     * Get reviews for a teacher
     * 
     * GET /api/reviews/teacher/{id}
     * Public
     */
    #[Route('/teacher/{id}', name: 'teacher_reviews', methods: ['GET'])]
    public function teacherReviews(Request $request, string $id): JsonResponse
    {
        $teacher = $this->entityManager->getRepository(User::class)->find($id);
        
        if ($teacher === null) {
            return $this->json([
                'success' => false,
                'error' => 'Teacher not found'
            ], 404);
        }
        
        $page = (int) $request->query->get('page', 1);
        $limit = min((int) $request->query->get('limit', 10), 50);
        
        $reviews = $this->entityManager->getRepository(Review::class)
            ->findBy(
                ['teacher' => $teacher, 'isPublic' => true],
                ['createdAt' => 'DESC'],
                $limit,
                ($page - 1) * $limit
            );
        
        $data = array_map(function ($review) {
            $student = $review->getStudent();
            $createdAt = $review->getCreatedAt();
            return [
                'id' => $review->getId(),
                'rating' => $review->getRating(),
                'ratingStars' => $review->getRatingStars(),
                'comment' => $review->getComment(),
                'student' => [
                    'firstName' => $student !== null ? $student->getFirstName() : 'Unknown',
                    'lastNameInitial' => $student !== null ? substr($student->getLastName(), 0, 1) . '.' : '?'
                ],
                'createdAt' => $createdAt !== null ? $createdAt->format('c') : null
            ];
        }, $reviews);
        
        return $this->json([
            'success' => true,
            'data' => [
                'reviews' => $data,
                'teacher' => [
                    'id' => $teacher->getId(),
                    'name' => $teacher->getFullName(),
                    'rating' => $teacher->getTeacherProfile()?->getRatingAvg(),
                    'reviewCount' => $teacher->getTeacherProfile()?->getReviewCount()
                ]
            ]
        ]);
    }

    /**
     * Get my reviews (as student)
     * 
     * GET /api/reviews/my
     * Student only
     */
    #[Route('/my', name: 'my', methods: ['GET'])]
    public function myReviews(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        
        /** @var User $student */
        $student = $this->getUser();
        
        $reviews = $this->entityManager->getRepository(Review::class)
            ->findBy(['student' => $student], ['createdAt' => 'DESC']);
        
        $data = array_map(function ($review) {
            $teacher = $review->getTeacher();
            $session = $review->getSession();
            $createdAt = $review->getCreatedAt();
            $booking = $session !== null ? $session->getBooking() : null;
            $timeSlot = $booking !== null ? $booking->getTimeSlot() : null;
            $date = $timeSlot !== null ? $timeSlot->getDate() : null;
            return [
                'id' => $review->getId(),
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
                'teacher' => [
                    'id' => $teacher !== null ? $teacher->getId() : null,
                    'name' => $teacher !== null ? $teacher->getFullName() : 'Unknown'
                ],
                'session' => [
                    'id' => $session !== null ? $session->getId() : null,
                    'date' => $date !== null ? $date->format('Y-m-d') : null
                ],
                'createdAt' => $createdAt !== null ? $createdAt->format('c') : null,
                'canEdit' => $review->canBeEdited()
            ];
        }, $reviews);
        
        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Update a review
     * 
     * PUT /api/reviews/{id}
     * Student only, within 24h of creation
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        
        $review = $this->entityManager->getRepository(Review::class)->find($id);
        
        if ($review === null) {
            return $this->json([
                'success' => false,
                'error' => 'Review not found'
            ], 404);
        }
        
        /** @var User $student */
        $student = $this->getUser();
        
        $reviewStudent = $review->getStudent();
        if ($reviewStudent === null || $reviewStudent->getId() !== $student->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'Not authorized'
            ], 403);
        }
        
        if (!$review->canBeEdited()) {
            return $this->json([
                'success' => false,
                'error' => 'Reviews can only be edited within 24 hours of creation'
            ], 400);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $oldRating = $review->getRating();
        
        if (isset($data['rating'])) {
            $review->setRating((int) $data['rating']);
        }
        
        if (isset($data['comment'])) {
            $review->setComment($data['comment']);
        }
        
        // Update teacher rating if rating changed
        if ($oldRating !== $review->getRating()) {
            $teacher = $review->getTeacher();
            $teacherProfile = $teacher !== null ? $teacher->getTeacherProfile() : null;
            if ($teacherProfile !== null) {
                $teacherProfile->recalculateRating($oldRating, $review->getRating());
            }
        }
        
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $review->getId(),
                'rating' => $review->getRating(),
                'comment' => $review->getComment()
            ]
        ]);
    }
}

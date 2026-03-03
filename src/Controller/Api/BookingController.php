<?php

namespace App\Controller\Api;

use App\DTO\BookingRequest;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Exception\BookingException;
use App\Exception\BusinessRuleViolationException;
use App\Service\Booking\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/bookings', name: 'api_bookings_')]
class BookingController extends AbstractController
{
    public function __construct(
        private BookingService $bookingService,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    /**
     * Create a new booking
     * 
     * POST /api/bookings
     * Student only
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        
        $student = $this->getUser();
        
        if (!$student instanceof \App\Entity\User) {
            return $this->json([
                'success' => false,
                'error' => 'User not authenticated properly'
            ], 401);
        }
        
        $data = json_decode($request->getContent(), true);
        
        // Validate required fields
        if (empty($data['teacherId']) || empty($data['timeSlotId'])) {
            return $this->json([
                'success' => false,
                'error' => 'teacherId and timeSlotId are required'
            ], 400);
        }
        
        try {
            $teacher = $this->entityManager->getRepository(User::class)->find($data['teacherId']);
            $timeSlot = $this->entityManager->getRepository(\App\Entity\TimeSlot::class)->find($data['timeSlotId']);
            $bundle = isset($data['bundleId']) ? $this->entityManager->getRepository(\App\Entity\Bundle::class)->find($data['bundleId']) : null;
            
            if ($teacher === null || $timeSlot === null) {
                return $this->json([
                    'success' => false,
                    'error' => 'Teacher or time slot not found'
                ], 404);
            }
            
            $booking = $this->bookingService->createBooking(
                $student,
                $teacher,
                $timeSlot,
                $bundle,
                $data['notes'] ?? null
            );
            
            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $booking->getId(),
                    'status' => $booking->getStatus(),
                    'teacher' => [
                        'id' => $teacher->getId(),
                        'name' => $teacher->getFullName()
                    ],
                    'timeSlot' => [
                        'date' => $timeSlot->getDate() !== null ? $timeSlot->getDate()->format('Y-m-d') : null,
                        'startTime' => $timeSlot->getStartTime() !== null ? $timeSlot->getStartTime()->format('H:i') : null,
                        'endTime' => $timeSlot->getEndTime() !== null ? $timeSlot->getEndTime()->format('H:i') : null
                    ],
                    'price' => $booking->getPrice(),
                    'bundleUsed' => $booking->usesBundle(),
                    'sessionsRemaining' => $bundle !== null ? $bundle->getSessionsRemaining() : null,
                    'createdAt' => $booking->getCreatedAt() !== null ? $booking->getCreatedAt()->format('c') : null
                ]
            ], 201);
            
        } catch (BookingException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode()
            ], 400);
        } catch (BusinessRuleViolationException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getRule()
            ], 400);
        }
    }

    /**
     * Confirm a booking
     * 
     * PATCH /api/bookings/{id}/confirm
     * Teacher only
     */
    #[Route('/{id}/confirm', name: 'confirm', methods: ['PATCH'])]
    public function confirm(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_TEACHER');
        
        /** @var User $teacher */
        $teacher = $this->getUser();
        
        $booking = $this->entityManager->getRepository(\App\Entity\Booking::class)->find($id);
        
        if ($booking === null) {
            return $this->json([
                'success' => false,
                'error' => 'Booking not found'
            ], 404);
        }
        
        try {
            $booking = $this->bookingService->confirmBooking($booking, $teacher);
            
            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $booking->getId(),
                    'status' => $booking->getStatus(),
                    'confirmedAt' => $booking->getConfirmedAt()?->format('c')
                ]
            ]);
            
        } catch (BusinessRuleViolationException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Cancel a booking
     * 
     * PATCH /api/bookings/{id}/cancel
     * Student or Teacher
     */
    #[Route('/{id}/cancel', name: 'cancel', methods: ['PATCH'])]
    public function cancel(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $booking = $this->entityManager->getRepository(\App\Entity\Booking::class)->find($id);
        
        if ($booking === null) {
            return $this->json([
                'success' => false,
                'error' => 'Booking not found'
            ], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        try {
            $booking = $this->bookingService->cancelBooking(
                $booking,
                $user,
                $data['reason'] ?? null
            );
            
            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $booking->getId(),
                    'status' => $booking->getStatus(),
                    'cancelledAt' => $booking->getCancelledAt()?->format('c'),
                    'bundleSessionRestored' => $booking->usesBundle()
                ]
            ]);
            
        } catch (BusinessRuleViolationException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get my bookings
     * 
     * GET /api/bookings/my
     */
    #[Route('/my', name: 'my', methods: ['GET'])]
    public function myBookings(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $status = $request->query->get('status');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        
        $bookings = $this->bookingService->getBookingHistory($user, $page, $limit);
        
        $data = array_map(function ($booking) {
            return [
                'id' => $booking->getId(),
                'status' => $booking->getStatus()->value,
                'teacher' => $booking->getTeacher() ? [
                    'id' => $booking->getTeacher()->getId(),
                    'name' => $booking->getTeacher()->getFullName()
                ] : null,
                'student' => $booking->getStudent() ? [
                    'id' => $booking->getStudent()->getId(),
                    'name' => $booking->getStudent()->getFullName()
                ] : null,
                'timeSlot' => $booking->getTimeSlot() ? [
                    'date' => $booking->getTimeSlot()->getDate()->format('Y-m-d'),
                    'startTime' => $booking->getTimeSlot()->getStartTime()->format('H:i'),
                    'endTime' => $booking->getTimeSlot()->getEndTime()->format('H:i')
                ] : null,
                'price' => $booking->getPrice(),
                'createdAt' => $booking->getCreatedAt()->format('c')
            ];
        }, $bookings);
        
        return $this->json([
            'success' => true,
            'data' => [
                'bookings' => $data,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit
                ]
            ]
        ]);
    }

    /**
     * Get a single booking
     * 
     * GET /api/bookings/{id}
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $booking = $this->entityManager->getRepository(\App\Entity\Booking::class)->find($id);
        
        if ($booking === null) {
            return $this->json([
                'success' => false,
                'error' => 'Booking not found'
            ], 404);
        }
        
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $booking->getId(),
                'status' => $booking->getStatus(),
                'student' => [
                    'id' => $booking->getStudent()->getId(),
                    'name' => $booking->getStudent()->getFullName()
                ],
                'teacher' => [
                    'id' => $booking->getTeacher()->getId(),
                    'name' => $booking->getTeacher()->getFullName()
                ],
                'timeSlot' => [
                    'date' => $booking->getTimeSlot()->getDate() !== null ? $booking->getTimeSlot()->getDate()->format('Y-m-d') : null,
                    'startTime' => $booking->getTimeSlot()->getStartTime() !== null ? $booking->getTimeSlot()->getStartTime()->format('H:i') : null,
                    'endTime' => $booking->getTimeSlot()->getEndTime() !== null ? $booking->getTimeSlot()->getEndTime()->format('H:i') : null
                ],
                'price' => $booking->getPrice(),
                'notes' => $booking->getNotes(),
                'bundle' => $booking->getBundle() !== null ? [
                    'id' => $booking->getBundle()->getId(),
                    'type' => $booking->getBundle()->getType() !== null ? $booking->getBundle()->getType()->value : null
                ] : null,
                'createdAt' => $booking->getCreatedAt() !== null ? $booking->getCreatedAt()->format('c') : null,
                'confirmedAt' => $booking->getConfirmedAt()?->format('c'),
                'cancelledAt' => $booking->getCancelledAt()?->format('c'),
                'completedAt' => $booking->getCompletedAt()?->format('c')
            ]
        ]);
    }
}

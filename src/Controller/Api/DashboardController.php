<?php

namespace App\Controller\Api;

use App\Entity\Booking;
use App\Entity\User;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/dashboard', name: 'api_dashboard_')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Teacher dashboard
     * 
     * GET /api/dashboard/teacher
     * Teacher only
     */
    #[Route('/teacher', name: 'teacher', methods: ['GET'])]
    public function teacherDashboard(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_TEACHER');
        
        /** @var User $teacher */
        $teacher = $this->getUser();
        $profile = $teacher->getTeacherProfile();
        
        // Get booking statistics
        $bookingRepo = $this->entityManager->getRepository(Booking::class);
        
        $stats = [
            'totalSessions' => $bookingRepo->count(['teacher' => $teacher]),
            'completedSessions' => $bookingRepo->count(['teacher' => $teacher, 'status' => BookingStatus::COMPLETED]),
            'cancelledSessions' => $bookingRepo->count(['teacher' => $teacher, 'status' => BookingStatus::CANCELLED]),
            'noShowSessions' => $bookingRepo->count(['teacher' => $teacher, 'status' => BookingStatus::NO_SHOW]),
        ];
        
        // Calculate completion rate
        $totalEnded = $stats['completedSessions'] + $stats['cancelledSessions'] + $stats['noShowSessions'];
        $stats['completionRate'] = $totalEnded > 0 
            ? round(($stats['completedSessions'] / $totalEnded) * 100, 1) 
            : 0;
        
        // Get earnings
        $qb = $bookingRepo->createQueryBuilder('b')
            ->select('SUM(b.price) as total')
            ->where('b.teacher = :teacher')
            ->andWhere('b.status = :completed')
            ->setParameter('teacher', $teacher)
            ->setParameter('completed', BookingStatus::COMPLETED);
        
        $stats['totalEarnings'] = (float) $qb->getQuery()->getSingleScalarResult();
        
        // Add profile stats
        if ($profile) {
            $stats['averageRating'] = $profile->getRatingAvgFloat();
            $stats['reviewCount'] = $profile->getReviewCount();
        }
        
        // Get upcoming bookings
        $upcomingQb = $bookingRepo->createQueryBuilder('b')
            ->join('b.timeSlot', 'ts')
            ->where('b.teacher = :teacher')
            ->andWhere('b.status IN (:statuses)')
            ->andWhere('ts.date >= :today')
            ->orderBy('ts.date', 'ASC')
            ->addOrderBy('ts.startTime', 'ASC')
            ->setParameter('teacher', $teacher)
            ->setParameter('statuses', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->setParameter('today', new \DateTime())
            ->setMaxResults(5);
        
        $upcomingBookings = $upcomingQb->getQuery()->getResult();
        
        $upcoming = array_map(function ($booking) {
            return [
                'id' => $booking->getId(),
                'studentName' => $booking->getStudent()->getFullName(),
                'date' => $booking->getTimeSlot()->getDate()->format('Y-m-d'),
                'time' => $booking->getTimeSlot()->getStartTime()->format('H:i') . '-' . $booking->getTimeSlot()->getEndTime()->format('H:i'),
                'status' => $booking->getStatus()->value
            ];
        }, $upcomingBookings);
        
        // Get recent reviews
        $reviews = $this->entityManager->getRepository(\App\Entity\Review::class)
            ->findBy(['teacher' => $teacher], ['createdAt' => 'DESC'], 3);
        
        $recentReviews = array_map(function ($review) {
            $student = $review->getStudent();
            $createdAt = $review->getCreatedAt();
            return [
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
                'studentName' => $student !== null ? $student->getFirstName() : 'Unknown',
                'createdAt' => $createdAt !== null ? $createdAt->format('c') : null
            ];
        }, $reviews);
        
        return $this->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'upcomingBookings' => $upcoming,
                'recentReviews' => $recentReviews
            ]
        ]);
    }

    /**
     * Student dashboard
     * 
     * GET /api/dashboard/student
     * Student only
     */
    #[Route('/student', name: 'student', methods: ['GET'])]
    public function studentDashboard(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');
        
        /** @var User $student */
        $student = $this->getUser();
        
        $bookingRepo = $this->entityManager->getRepository(Booking::class);
        
        // Get booking statistics
        $stats = [
            'totalBookings' => $bookingRepo->count(['student' => $student]),
            'completedSessions' => $bookingRepo->count(['student' => $student, 'status' => BookingStatus::COMPLETED]),
            'upcomingSessions' => $bookingRepo->createQueryBuilder('b')
                ->select('COUNT(b.id)')
                ->join('b.timeSlot', 'ts')
                ->where('b.student = :student')
                ->andWhere('b.status IN (:statuses)')
                ->andWhere('ts.date >= :today')
                ->setParameter('student', $student)
                ->setParameter('statuses', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
                ->setParameter('today', new \DateTime())
                ->getQuery()
                ->getSingleScalarResult(),
        ];
        
        // Get bundle stats
        $bundleStats = [
            'activeBundles' => $this->entityManager->getRepository(\App\Entity\Bundle::class)
                ->count(['student' => $student, 'status' => \App\Entity\Bundle::STATUS_ACTIVE]),
            'totalSessionsRemaining' => 0
        ];
        
        $bundles = $this->entityManager->getRepository(\App\Entity\Bundle::class)
            ->findBy(['student' => $student, 'status' => \App\Entity\Bundle::STATUS_ACTIVE]);
        
        foreach ($bundles as $bundle) {
            $bundleStats['totalSessionsRemaining'] += $bundle->getSessionsRemaining();
        }
        
        // Get upcoming bookings
        $upcomingQb = $bookingRepo->createQueryBuilder('b')
            ->join('b.timeSlot', 'ts')
            ->where('b.student = :student')
            ->andWhere('b.status IN (:statuses)')
            ->andWhere('ts.date >= :today')
            ->orderBy('ts.date', 'ASC')
            ->addOrderBy('ts.startTime', 'ASC')
            ->setParameter('student', $student)
            ->setParameter('statuses', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->setParameter('today', new \DateTime())
            ->setMaxResults(5);
        
        $upcomingBookings = $upcomingQb->getQuery()->getResult();
        
        $upcoming = array_map(function ($booking) {
            return [
                'id' => $booking->getId(),
                'teacherName' => $booking->getTeacher()->getFullName(),
                'date' => $booking->getTimeSlot()->getDate()->format('Y-m-d'),
                'time' => $booking->getTimeSlot()->getStartTime()->format('H:i') . '-' . $booking->getTimeSlot()->getEndTime()->format('H:i'),
                'status' => $booking->getStatus()->value,
                'price' => $booking->getPrice()
            ];
        }, $upcomingBookings);
        
        return $this->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'bundleStats' => $bundleStats,
                'upcomingBookings' => $upcoming
            ]
        ]);
    }

    /**
     * Admin dashboard
     * 
     * GET /api/dashboard/admin
     * Admin only
     */
    #[Route('/admin', name: 'admin', methods: ['GET'])]
    public function adminDashboard(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $userRepo = $this->entityManager->getRepository(User::class);
        $bookingRepo = $this->entityManager->getRepository(Booking::class);
        
        // User statistics
        $stats = [
            'totalUsers' => $userRepo->count([]),
            'totalStudents' => 0,
            'totalTeachers' => 0,
            'totalSessions' => $bookingRepo->count([]),
            'completedSessions' => $bookingRepo->count(['status' => BookingStatus::COMPLETED]),
        ];
        
        // Count by role
        $users = $userRepo->findAll();
        foreach ($users as $user) {
            if ($user->hasRole(\App\Enum\UserRole::TEACHER)) {
                $stats['totalTeachers']++;
            }
            if ($user->hasRole(\App\Enum\UserRole::STUDENT)) {
                $stats['totalStudents']++;
            }
        }
        
        // Revenue
        $qb = $bookingRepo->createQueryBuilder('b')
            ->select('SUM(b.price) as total')
            ->where('b.status = :completed')
            ->setParameter('completed', BookingStatus::COMPLETED);
        
        $stats['totalRevenue'] = (float) $qb->getQuery()->getSingleScalarResult();
        
        // Average session price
        $stats['averageSessionPrice'] = $stats['completedSessions'] > 0 
            ? round($stats['totalRevenue'] / $stats['completedSessions'], 2)
            : 0;
        
        // Recent signups
        $recentUsers = $userRepo->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );
        
        $recentSignups = array_map(function ($user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'createdAt' => $user->getCreatedAt()?->format('c')
            ];
        }, $recentUsers);
        
        // Top teachers by rating
        $topTeachers = $this->entityManager->getRepository(\App\Entity\TeacherProfile::class)
            ->findBy(['isVerified' => true], ['ratingAvg' => 'DESC'], 5);
        
        $topTeacherData = array_map(function ($profile) {
            $user = $profile->getUser();
            return [
                'id' => $user !== null ? $user->getId() : null,
                'name' => $user !== null ? $user->getFullName() : 'Unknown',
                'rating' => $profile->getRatingAvg(),
                'reviewCount' => $profile->getReviewCount(),
                'subjects' => $profile->getSubjects()
            ];
        }, $topTeachers);
        
        return $this->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recentSignups' => $recentSignups,
                'topTeachers' => $topTeacherData
            ]
        ]);
    }
}

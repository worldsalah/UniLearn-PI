<?php

namespace App\Controller;

use App\Entity\TeacherProfile;
use App\Entity\User;
use App\Repository\TeacherProfileRepository;
use App\Repository\BookingRepository;
use App\Repository\BundleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tutoring')]
class TutoringController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Teacher listing page
     */
    #[Route('/teachers', name: 'tutoring_teachers')]
    public function teachers(Request $request): Response
    {
        $subject = $request->query->get('subject');
        $minRating = $request->query->get('minRating');
        $maxPrice = $request->query->get('maxPrice');
        $page = (int) $request->query->get('page', 1);
        $limit = 12;

        $qb = $this->entityManager->getRepository(TeacherProfile::class)
            ->createQueryBuilder('tp')
            ->join('tp.user', 'u')
            ->andWhere('tp.isVerified = true');

        if (is_string($subject) && $subject !== '') {
            $qb->andWhere('JSON_CONTAINS(tp.subjects, :subject) = 1')
               ->setParameter('subject', json_encode($subject));
        }

        if ($minRating !== null) {
            $qb->andWhere('tp.ratingAvg >= :minRating')
               ->setParameter('minRating', (float) $minRating);
        }

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

        return $this->render('tutoring/teachers.html.twig', [
            'teachers' => $teachers,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'filters' => [
                'subject' => $subject,
                'minRating' => $minRating,
                'maxPrice' => $maxPrice,
            ]
        ]);
    }

    /**
     * Teacher profile page
     */
    #[Route('/teachers/{id}', name: 'tutoring_teacher_profile')]
    public function teacherProfile(string $id, Request $request): Response
    {
        $teacherProfile = $this->entityManager->getRepository(TeacherProfile::class)->find($id);

        if ($teacherProfile === null) {
            throw $this->createNotFoundException('Teacher not found');
        }

        // Get availability for next 7 days
        $startDate = new \DateTime();
        $endDate = (clone $startDate)->modify('+7 days');

        $slots = $this->entityManager->getRepository(\App\Entity\TimeSlot::class)
            ->createQueryBuilder('ts')
            ->join('ts.availability', 'a')
            ->where('a.teacher = :teacher')
            ->andWhere('ts.date >= :startDate')
            ->andWhere('ts.date <= :endDate')
            ->andWhere('ts.status = :status')
            ->setParameter('teacher', $teacherProfile)
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->setParameter('status', 'available')
            ->orderBy('ts.date', 'ASC')
            ->addOrderBy('ts.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        // Get reviews
        $reviews = $this->entityManager->getRepository(\App\Entity\Review::class)
            ->findBy(['teacher' => $teacherProfile->getUser()], ['createdAt' => 'DESC'], 5);

        return $this->render('tutoring/teacher_profile.html.twig', [
            'teacher' => $teacherProfile,
            'slots' => $slots,
            'reviews' => $reviews,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Student dashboard
     */
    #[Route('/dashboard/student', name: 'tutoring_student_dashboard')]
    public function studentDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        /** @var User $user */
        $user = $this->getUser();

        // Get upcoming bookings
        $upcomingBookings = $this->entityManager->getRepository(\App\Entity\Booking::class)
            ->createQueryBuilder('b')
            ->join('b.timeSlot', 'ts')
            ->where('b.student = :student')
            ->andWhere('ts.date >= :today')
            ->andWhere('b.status IN (:statuses)')
            ->orderBy('ts.date', 'ASC')
            ->addOrderBy('ts.startTime', 'ASC')
            ->setParameter('student', $user)
            ->setParameter('today', new \DateTime())
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Get bundles
        $bundles = $this->entityManager->getRepository(\App\Entity\Bundle::class)
            ->findBy(['student' => $user, 'status' => 'active']);

        // Get stats
        $totalBookings = $this->entityManager->getRepository(\App\Entity\Booking::class)
            ->count(['student' => $user]);

        $completedSessions = $this->entityManager->getRepository(\App\Entity\Booking::class)
            ->count(['student' => $user, 'status' => 'completed']);

        return $this->render('tutoring/student_dashboard.html.twig', [
            'upcomingBookings' => $upcomingBookings,
            'bundles' => $bundles,
            'stats' => [
                'totalBookings' => $totalBookings,
                'completedSessions' => $completedSessions,
                'upcomingSessions' => count($upcomingBookings),
            ]
        ]);
    }

    /**
     * Teacher dashboard
     */
    #[Route('/dashboard/teacher', name: 'tutoring_teacher_dashboard')]
    public function teacherDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        /** @var User $user */
        $user = $this->getUser();
        $profile = $this->entityManager->getRepository(TeacherProfile::class)->findOneBy(['user' => $user]);

        if ($profile === null) {
            return $this->redirectToRoute('tutoring_availability');
        }

        // Get today's sessions
        $today = new \DateTime();
        $todayBookings = $this->entityManager->getRepository(\App\Entity\Booking::class)
            ->createQueryBuilder('b')
            ->join('b.timeSlot', 'ts')
            ->where('b.teacher = :teacher')
            ->andWhere('ts.date = :today')
            ->andWhere('b.status IN (:statuses)')
            ->orderBy('ts.startTime', 'ASC')
            ->setParameter('teacher', $user)
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->getQuery()
            ->getResult();

        // Get pending requests
        $pendingBookings = $this->entityManager->getRepository(\App\Entity\Booking::class)
            ->findBy(['teacher' => $user, 'status' => 'pending'], ['createdAt' => 'DESC'], 5);

        // Get stats
        $totalStudents = $this->entityManager->getRepository(\App\Entity\Booking::class)
            ->createQueryBuilder('b')
            ->select('COUNT(DISTINCT b.student)')
            ->where('b.teacher = :teacher')
            ->setParameter('teacher', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $weekSessions = $this->entityManager->getRepository(\App\Entity\Booking::class)
            ->createQueryBuilder('b')
            ->join('b.timeSlot', 'ts')
            ->where('b.teacher = :teacher')
            ->andWhere('ts.date >= :weekStart')
            ->setParameter('teacher', $user)
            ->setParameter('weekStart', (clone $today)->modify('monday this week'))
            ->getQuery()
            ->getResult();

        return $this->render('tutoring/teacher_dashboard.html.twig', [
            'profile' => $profile,
            'todayBookings' => $todayBookings,
            'pendingBookings' => $pendingBookings,
            'stats' => [
                'totalStudents' => $totalStudents,
                'weekSessions' => count($weekSessions),
                'rating' => $profile->getRatingAvg(),
                'reviewCount' => $profile->getReviewCount(),
            ]
        ]);
    }

    /**
     * Set availability page
     */
    #[Route('/availability', name: 'tutoring_availability')]
    public function availability(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        /** @var User $user */
        $user = $this->getUser();
        $profile = $this->entityManager->getRepository(TeacherProfile::class)->findOneBy(['user' => $user]);

        // Get current availability
        $availabilities = [];
        if ($profile !== null) {
            $availabilities = $this->entityManager->getRepository(\App\Entity\Availability::class)
                ->findBy(['teacher' => $profile, 'isActive' => true], ['dayOfWeek' => 'ASC']);
        }

        // Get all active categories for subject selection
        $categories = $this->entityManager->getRepository(\App\Entity\Category::class)
            ->findBy(['isActive' => true], ['name' => 'ASC']);

        return $this->render('tutoring/availability.html.twig', [
            'profile' => $profile,
            'availabilities' => $availabilities,
            'categories' => $categories,
        ]);
    }

    /**
     * My bookings page
     */
    #[Route('/bookings', name: 'tutoring_bookings')]
    public function bookings(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var User $user */
        $user = $this->getUser();

        $isTeacher = $user->hasRole('ROLE_INSTRUCTOR');

        $bookings = $this->entityManager->getRepository(\App\Entity\Booking::class)
            ->createQueryBuilder('b')
            ->join('b.timeSlot', 'ts')
            ->where($isTeacher ? 'b.teacher = :user' : 'b.student = :user')
            ->orderBy('ts.date', 'DESC')
            ->addOrderBy('ts.startTime', 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $this->render('tutoring/bookings.html.twig', [
            'bookings' => $bookings,
            'isTeacher' => $isTeacher,
        ]);
    }

    /**
     * My bundles page
     */
    #[Route('/bundles', name: 'tutoring_bundles')]
    public function bundles(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        /** @var User $user */
        $user = $this->getUser();

        $bundles = $this->entityManager->getRepository(\App\Entity\Bundle::class)
            ->findBy(['student' => $user], ['createdAt' => 'DESC']);

        return $this->render('tutoring/bundles.html.twig', [
            'bundles' => $bundles,
        ]);
    }
}

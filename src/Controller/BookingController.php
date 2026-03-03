<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\CategoryRepository;
use App\Repository\MeetingFeedbackRepository;
use App\Repository\RoleRepository;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    #[Route('/teacher/calendar', name: 'teacher_calendar')]
    public function teacherCalendar(
        Request $request,
        BookingRepository $bookingRepository,
        SessionRepository $sessionRepository,
    ): Response {
        // Check if user is logged in and is an instructor
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // Get the instructor's sessions
        $sessions = $sessionRepository->findBy(['instructor' => $user]);

        // Get month and year from query params, default to current
        $month = (int) $request->query->get('month', date('n'));
        $year = (int) $request->query->get('year', date('Y'));

        // Validate month and year
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2020 || $year > 2030) {
            $year = (int) date('Y');
        }

        // Get all bookings for this instructor for the selected month
        $startDate = new \DateTime("$year-$month-01");
        $startDate->setTime(0, 0, 0);
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        $endDate->setTime(23, 59, 59);

        // Get bookings for all sessions of this instructor (exclude denied bookings)
        $bookings = $bookingRepository->createQueryBuilder('b')
            ->innerJoin('b.session', 's')
            ->where('s.instructor = :instructor')
            ->andWhere('b.preferredDate >= :startDate')
            ->andWhere('b.preferredDate <= :endDate')
            ->andWhere('b.status != :deniedStatus')
            ->setParameter('instructor', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('deniedStatus', 'denied')
            ->orderBy('b.preferredDate', 'ASC')
            ->addOrderBy('b.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        // Group bookings by date
        $bookingsByDate = [];
        foreach ($bookings as $booking) {
            $dateKey = $booking->getPreferredDate()->format('Y-m-d');
            if (!isset($bookingsByDate[$dateKey])) {
                $bookingsByDate[$dateKey] = [];
            }
            $bookingsByDate[$dateKey][] = $booking;
        }

        // Calculate previous and next month
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        return $this->render('Front-office/booking/teacher_calendar.html.twig', [
            'sessions' => $sessions,
            'bookingsByDate' => $bookingsByDate,
            'currentMonth' => $month,
            'currentYear' => $year,
            'prevMonth' => $prevMonth,
            'prevYear' => $prevYear,
            'nextMonth' => $nextMonth,
            'nextYear' => $nextYear,
            'monthName' => date('F', mktime(0, 0, 0, $month, 1) ?: null),
        ]);
    }

    #[Route('/meeting/{id}', name: 'booking_meeting')]
    public function meeting(
        int $id,
        BookingRepository $bookingRepository,
    ): Response {
        $booking = $bookingRepository->find($id);

        if ($booking === null) {
            throw $this->createNotFoundException('Booking not found');
        }

        // Check if user is authorized (either the instructor or the student)
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // Verify authorization - user must be the session instructor or the booking user
        $session = $booking->getSession();
        $isInstructor = $session !== null && $session->getInstructor() === $user;
        $isStudent = $booking->getUser() === $user;

        if (!$isInstructor && !$isStudent) {
            throw $this->createAccessDeniedException('You are not authorized to join this meeting');
        }

        // Generate a unique room name based on booking ID
        $roomName = 'UniLearnSession' . $id;

        // Get participant names
        $instructorName = $session !== null && $session->getInstructor() !== null
            ? $session->getInstructor()->getFullName()
            : 'Instructor';
        $studentName = $booking->getFirstName() . ' ' . $booking->getLastName();

        return $this->render('Front-office/booking/meeting.html.twig', [
            'booking' => $booking,
            'roomName' => $roomName,
            'instructorName' => $instructorName,
            'studentName' => $studentName,
            'isInstructor' => $isInstructor,
        ]);
    }

    #[Route('/booking/{id}/send-link', name: 'booking_send_link', methods: ['POST'])]
    public function sendMeetingLink(
        int $id,
        BookingRepository $bookingRepository,
        \App\Service\BookingNotificationService $notificationService,
    ): JsonResponse {
        $booking = $bookingRepository->find($id);

        if ($booking === null) {
            return $this->json([
                'success' => false,
                'error' => 'Booking not found'
            ], 404);
        }

        // Check if user is authorized (must be the instructor)
        $user = $this->getUser();
        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }

        $session = $booking->getSession();
        $isInstructor = $session !== null && $session->getInstructor() === $user;

        if (!$isInstructor) {
            return $this->json([
                'success' => false,
                'error' => 'You are not authorized to send this link'
            ], 403);
        }

        try {
            // Send meeting invitation email
            $notificationService->sendMeetingInvitation($booking);

            return $this->json([
                'success' => true,
                'message' => 'Meeting link sent successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/booking/{id}/feedback', name: 'api_booking_feedback', methods: ['POST'])]
    public function submitMeetingFeedback(
        int $id,
        Request $request,
        BookingRepository $bookingRepository,
        MeetingFeedbackRepository $feedbackRepository,
    ): JsonResponse {
        $booking = $bookingRepository->find($id);

        if ($booking === null) {
            return $this->json([
                'success' => false,
                'error' => 'Booking not found'
            ], 404);
        }

        $user = $this->getUser();
        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }

        // Check if user is part of this booking
        $session = $booking->getSession();
        $isInstructor = $session !== null && $session->getInstructor() === $user;
        $isStudent = $booking->getUser() === $user;

        if (!$isInstructor && !$isStudent) {
            return $this->json([
                'success' => false,
                'error' => 'You are not authorized to submit feedback for this meeting'
            ], 403);
        }

        // Check if user already submitted feedback
        if ($feedbackRepository->hasUserSubmittedFeedback($id, $user->getId())) {
            return $this->json([
                'success' => false,
                'error' => 'You have already submitted feedback for this meeting'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);

        // Validate ratings
        $satisfaction = $data['satisfactionRating'] ?? 0;
        $callQuality = $data['callQualityRating'] ?? 0;
        $learningStyle = $data['learningStyleRating'] ?? 0;

        if ($satisfaction < 1 || $satisfaction > 5 || $callQuality < 1 || $callQuality > 5 || $learningStyle < 1 || $learningStyle > 5) {
            return $this->json([
                'success' => false,
                'error' => 'All ratings must be between 1 and 5 stars'
            ], 400);
        }

        // Create feedback
        $feedback = new \App\Entity\MeetingFeedback();
        $feedback->setBooking($booking);
        if ($user instanceof \App\Entity\User) {
            $feedback->setUser($user);
        }
        $feedback->setSatisfactionRating($satisfaction);
        $feedback->setCallQualityRating($callQuality);
        $feedback->setLearningStyleRating($learningStyle);
        $feedback->setComments($data['comments'] ?? null);
        $feedback->setUserRole($isInstructor ? 'instructor' : 'student');

        $feedbackRepository->save($feedback);

        return $this->json([
            'success' => true,
            'message' => 'Thank you for your feedback!',
            'averageRating' => $feedback->getAverageRating()
        ]);
    }

    #[Route('/api/booking/{id}/feedback/status', name: 'api_booking_feedback_status', methods: ['GET'])]
    public function getFeedbackStatus(
        int $id,
        BookingRepository $bookingRepository,
        MeetingFeedbackRepository $feedbackRepository,
    ): JsonResponse {
        $booking = $bookingRepository->find($id);

        if ($booking === null) {
            return $this->json([
                'success' => false,
                'error' => 'Booking not found'
            ], 404);
        }

        $user = $this->getUser();
        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }

        $hasSubmitted = $feedbackRepository->hasUserSubmittedFeedback($id, $user->getId());
        $averageRatings = $feedbackRepository->getAverageRatingsForBooking($id);

        return $this->json([
            'success' => true,
            'hasSubmitted' => $hasSubmitted,
            'averageRatings' => $averageRatings
        ]);
    }

    #[Route('/booking', name: 'booking_create')]
    public function new(
        Request $request,
        BookingRepository $bookingRepository,
        SessionRepository $sessionRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
    ): Response {
        // Get the session_id from query parameter if provided
        $selectedSessionId = $request->query->get('session_id');
        $selectedSession = null;

        if ($selectedSessionId !== null && $selectedSessionId !== '') {
            $selectedSession = $sessionRepository->find($selectedSessionId);
        }

        //  Get all sessions BEFORE submit
        $sessions = $sessionRepository->findSessionsWithInstructorInfo();

        // Get all active categories
        $categories = $categoryRepository->findBy(['isActive' => true], ['name' => 'ASC']);

        // Get instructors (users with instructor role)
        $instructorRole = $roleRepository->findOneBy(['name' => 'instructor']);
        $instructors = [];
        if ($instructorRole !== null) {
            $instructors = $userRepository->findBy(['role' => $instructorRole], ['fullName' => 'ASC']);
        } else {
            // Try alternative role name
            $instructorRole = $roleRepository->findOneBy(['name' => 'ROLE_INSTRUCTOR']);
            if ($instructorRole !== null) {
                $instructors = $userRepository->findBy(['role' => $instructorRole], ['fullName' => 'ASC']);
            }
        }

        // Get students (users with student role)
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        $students = [];
        if ($studentRole !== null) {
            $students = $userRepository->findBy(['role' => $studentRole], ['fullName' => 'ASC']);
        } else {
            // Try alternative role name
            $studentRole = $roleRepository->findOneBy(['name' => 'ROLE_STUDENT']);
            if ($studentRole !== null) {
                $students = $userRepository->findBy(['role' => $studentRole], ['fullName' => 'ASC']);
            }
        }

        if ($request->isMethod('POST')) {
            $booking = new Booking();
            $errors = [];

            // Validate form data
            $firstName = $request->request->get('firstName');
            $userEmail = $request->request->get('userEmail');
            $preferredDate = $request->request->get('preferred_date');
            $message = $request->request->get('message');
            $terms = $request->request->get('termsCheck');

            // Role-based field validation
            $instructorId = null;
            $studentId = null;
            $allUserId = null;

            $currentUser = $this->getUser();
            if ($currentUser !== null && $currentUser->getRole() !== null && 'student' === $currentUser->getRole()->getName()) {
                // For students, instructor selection is only required if no session is pre-selected
                if ($selectedSession === null) {
                    $instructorId = $request->request->get('instructor_id');
                    if (empty($instructorId)) {
                        $errors['instructor_id'] = 'Please select an instructor for your session.';
                    }
                }
            // If session is pre-selected, instructor will be assigned automatically
            } elseif ($currentUser !== null && $currentUser->getRole() !== null && 'instructor' === $currentUser->getRole()->getName()) {
                // Instructors don't need to select students - students book directly
                // No validation needed for instructors
            } else {
                $allUserId = $request->request->get('all_users');
                if (empty($allUserId)) {
                    $errors['all_users'] = 'Please select a user from the list.';
                }
            }

            // Common validations
            if (empty($firstName)) {
                $errors['firstName'] = 'Full name is required.';
            }

            // Session validation
            $sessionId = $request->request->get('session_id');
            if (empty($sessionId)) {
                $errors['session_id'] = 'Please select a session.';
            }

            if (empty($userEmail)) {
                $errors['userEmail'] = 'Email address is required.';
            } elseif (filter_var($userEmail, FILTER_VALIDATE_EMAIL) === false) {
                $errors['userEmail'] = 'Please enter a valid email address.';
            }

            if (empty($preferredDate)) {
                $errors['preferred_date'] = 'Preferred date is required.';
            } else {
                $preferredDateStr = is_string($preferredDate) ? $preferredDate : (string) $preferredDate;
                $date = \DateTime::createFromFormat('Y-m-d', $preferredDateStr);
                if ($date === false) {
                    $errors['preferred_date'] = 'Please enter a valid date.';
                } else {
                    $today = new \DateTime();
                    $today->setTime(0, 0, 0);
                    if ($date < $today) {
                        $errors['preferred_date'] = 'Please select a date that is today or in the future.';
                    }

                    $maxDate = new \DateTime();
                    $maxDate->modify('+6 months');
                    if ($date > $maxDate) {
                        $errors['preferred_date'] = 'Please select a date within the next 6 months.';
                    }
                }
            }

            // Time slot validation
            $startTimeStr = $request->request->get('start_time');
            $durationMinutes = $request->request->get('duration_minutes');
            $totalPrice = $request->request->get('total_price');

            if (empty($startTimeStr)) {
                $errors['start_time'] = 'Please select a start time.';
            }

            if (empty($durationMinutes)) {
                $errors['duration_minutes'] = 'Please select a duration.';
            } elseif (!is_numeric($durationMinutes) || (int) $durationMinutes <= 0) {
                $errors['duration_minutes'] = 'Duration must be a positive number.';
            }

            if (empty($terms)) {
                $errors['terms'] = 'You must accept the terms and conditions.';
            }

            // If there are errors, re-render the form with errors
            if (!empty($errors)) {
                return $this->render('Front-office/booking/index.html.twig', [
                    'sessions' => $sessions,
                    'categories' => $categories,
                    'instructors' => $instructors,
                    'students' => $students,
                    'allUsers' => $userRepository->findAll(),
                    'errors' => $errors,
                    'formData' => $request->request->all(),
                    'selectedSession' => $selectedSession,
                ]);
            }

            // Process valid booking
            $booking = new Booking();

            // Set the user relationship if logged in
            $currentUser = $this->getUser();
            if ($currentUser !== null) {
                $user = $currentUser;
                $booking->setUser($user instanceof \App\Entity\User ? $user : null);
                $booking->setFirstName((string) ($request->request->get('firstName') ?: $user->getFullName()));
                $booking->setUserEmail((string) ($request->request->get('userEmail') ?: $user->getEmail()));
            } else {
                $booking->setFirstName((string) $request->request->get('firstName'));
                $booking->setUserEmail((string) $request->request->get('userEmail'));
            }

            // Set preferred date
            $preferredDate = $request->request->get('preferred_date');
            if ($preferredDate !== null && $preferredDate !== '') {
                $booking->setPreferredDate(new \DateTime((string) $preferredDate));
            }

            // Set creation date
            $booking->setCreatedAt(new \DateTime());

            // Set default status
            $booking->setStatus('pending');

            // Set lastName (split from firstName if needed)
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            if ($firstName !== null && $firstName !== '' && ($lastName === null || $lastName === '')) {
                $nameParts = explode(' ', (string) $firstName, 2);
                $booking->setFirstName($nameParts[0]);
                $booking->setLastName($nameParts[1] ?? '');
            } else {
                $booking->setFirstName((string) $firstName);
                $booking->setLastName((string) ($lastName ?? ''));
            }

            // Link the booking to the selected session
            $sessionId = $request->request->get('session_id');
            if ($sessionId !== null && $sessionId !== '') {
                $session = $sessionRepository->find($sessionId);
                if ($session instanceof \App\Entity\Session) {
                    $booking->setSession($session);
                }
            }

            // Set start time
            $startTimeStr = $request->request->get('start_time');
            if ($startTimeStr !== null && $startTimeStr !== '') {
                try {
                    $startTime = new \DateTime((string) $startTimeStr);
                    $booking->setStartTime($startTime);
                } catch (\Exception $e) {
                    // Invalid time format, will be caught by validation
                }
            }

            // Set duration
            $durationMinutes = $request->request->get('duration_minutes');
            if ($durationMinutes !== null && $durationMinutes !== '') {
                $booking->setDurationMinutes((int) $durationMinutes);
            }

            // Set total price
            $totalPrice = $request->request->get('total_price');
            if ($totalPrice !== null && $totalPrice !== '') {
                $booking->setTotalPrice(number_format((float) $totalPrice, 2, '.', ''));
            }

            $bookingRepository->save($booking);

            return $this->redirectToRoute('booking_create');
        }

        return $this->render('Front-office/booking/index.html.twig', [
            'sessions' => $sessions,
            'categories' => $categories,
            'instructors' => $instructors,
            'students' => $students,
            'allUsers' => $userRepository->findAll(),
            'selectedSession' => $selectedSession,
        ]);
    }

    #[Route('/bookings', name: 'all_bookings')]
    public function getAllBookings(BookingRepository $bookingRepository): Response
    {
        // Get all bookings using your repository method
        $bookings = $bookingRepository->findAllBookings();

        // Render the template and pass the bookings
        return $this->render('/Front-office/booking/bookingList.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/booking-data', name: 'booking_data_display')]
    public function displayBookingData(
        BookingRepository $bookingRepository,
    ): Response {
        // Get all bookings
        $bookings = $bookingRepository->findAll();

        // Render the booking display template
        return $this->render('Front-office/booking/bookingDisplay.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/bookings/update', name: 'booking_update', methods: ['POST'])]
    public function update(
        Request $request,
        BookingRepository $bookingRepository,
    ): Response {
        $booking = $bookingRepository->find($request->request->get('id'));

        if ($booking === null) {
            throw $this->createNotFoundException('Booking not found');
        }

        $booking->setFirstName((string) $request->request->get('firstName'));
        $booking->setLastName((string) $request->request->get('lastName'));
        $booking->setUserEmail((string) $request->request->get('userEmail'));
        $booking->setPhoneNumber((string) $request->request->get('phoneNumber'));

        $bookingRepository->save($booking, true);

        return $this->redirectToRoute('all_bookings');
    }

    #[Route('/booking/{id}/accept', name: 'booking_accept', methods: ['POST'])]
    public function acceptBooking(
        int $id,
        BookingRepository $bookingRepository,
        \App\Service\BookingNotificationService $notificationService,
    ): Response {
        $booking = $bookingRepository->find($id);
        if ($booking === null) {
            throw $this->createNotFoundException('Booking not found');
        }

        $booking->setStatus('accepted');
        $booking->setUpdatedAt(new \DateTime());
        $bookingRepository->save($booking, true);

        // Send meeting invitation email to student
        $notificationService->sendMeetingInvitation($booking);

        $this->addFlash('success', 'Booking has been accepted successfully! Meeting invitation sent to student.');

        return $this->redirectToRoute('all_bookings');
    }

    #[Route('/booking/{id}/deny', name: 'booking_deny', methods: ['POST'])]
    public function denyBooking(
        int $id,
        BookingRepository $bookingRepository,
    ): Response {
        $booking = $bookingRepository->find($id);
        if ($booking === null) {
            throw $this->createNotFoundException('Booking not found');
        }

        $booking->setStatus('denied');
        $booking->setUpdatedAt(new \DateTime());
        $bookingRepository->save($booking, true);

        $this->addFlash('warning', 'Booking has been denied.');

        return $this->redirectToRoute('all_bookings');
    }

    #[Route('/booking/{id}/delete', name: 'booking_delete', methods: ['DELETE', 'POST'])]
    public function delete(
        int $id,
        Request $request,
        BookingRepository $bookingRepository,
        EntityManagerInterface $em,
    ): Response {
        $booking = $bookingRepository->find($id);

        if ($booking === null) {
            if ($request->isXmlHttpRequest()) {
                return new Response('Booking not found', 404);
            }
            throw $this->createNotFoundException();
        }

        // For AJAX requests, skip CSRF validation for simplicity
        // In production, you should implement proper CSRF protection for AJAX
        $token = $request->request->get('_token');
        if ($request->isMethod('DELETE') || $this->isCsrfTokenValid('delete_booking_'.$id, $token !== null ? (string) $token : null)) {
            $em->remove($booking);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response('Booking deleted successfully', 200);
            }

            $this->addFlash('success', 'Booking deleted successfully');
        } elseif ($request->isXmlHttpRequest()) {
            return new Response('Invalid CSRF token', 403);
        }

        return $this->redirectToRoute('all_bookings');
    }

    #[Route('/booking/{id}/view', name: 'booking_view')]
    public function view(
        int $id,
        BookingRepository $bookingRepository,
    ): Response {
        $booking = $bookingRepository->find($id);

        if ($booking === null) {
            throw $this->createNotFoundException('Booking not found');
        }

        return $this->render('Front-office/booking/view.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/booking/{id}/edit', name: 'booking_edit')]
    public function edit(
        int $id,
        Request $request,
        BookingRepository $bookingRepository,
        SessionRepository $sessionRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
    ): Response {
        $booking = $bookingRepository->find($id);

        if ($booking === null) {
            throw $this->createNotFoundException('Booking not found');
        }

        // Get all sessions for the form
        $sessions = $sessionRepository->findSessionsWithInstructorInfo();

        // Get all active categories
        $categories = $categoryRepository->findBy(['isActive' => true], ['name' => 'ASC']);

        // Get instructors (users with instructor role)
        $instructorRole = $roleRepository->findOneBy(['name' => 'instructor']);
        $instructors = [];
        if ($instructorRole !== null) {
            $instructors = $userRepository->findBy(['role' => $instructorRole], ['fullName' => 'ASC']);
        }

        // Get students (users with student role)
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        $students = [];
        if ($studentRole !== null) {
            $students = $userRepository->findBy(['role' => $studentRole], ['fullName' => 'ASC']);
        }

        if ($request->isMethod('POST')) {
            $errors = [];

            // Validate form data
            $firstName = $request->request->get('firstName');
            $userEmail = $request->request->get('userEmail');
            $preferredDate = $request->request->get('preferred_date');

            // Common validations
            if (empty($firstName)) {
                $errors['firstName'] = 'Full name is required.';
            }

            if (empty($userEmail)) {
                $errors['userEmail'] = 'Email address is required.';
            } elseif (filter_var($userEmail, FILTER_VALIDATE_EMAIL) === false) {
                $errors['userEmail'] = 'Please enter a valid email address.';
            }

            if (empty($preferredDate)) {
                $errors['preferred_date'] = 'Preferred date is required.';
            } else {
                $date = \DateTime::createFromFormat('Y-m-d', (string) $preferredDate);
                if ($date === false) {
                    $errors['preferred_date'] = 'Please enter a valid date.';
                }
            }

            // If there are errors, re-render the form with errors
            if (!empty($errors)) {
                return $this->render('Front-office/booking/edit.html.twig', [
                    'booking' => $booking,
                    'sessions' => $sessions,
                    'categories' => $categories,
                    'instructors' => $instructors,
                    'students' => $students,
                    'allUsers' => $userRepository->findAll(),
                    'errors' => $errors,
                    'formData' => $request->request->all(),
                ]);
            }

            // Update booking
            $booking->setFirstName((string) $firstName);
            $booking->setUserEmail((string) $userEmail);
            $booking->setPhoneNumber((string) $request->request->get('phoneNumber'));

            // Set preferred date
            if ($preferredDate !== null && $preferredDate !== '') {
                $booking->setPreferredDate(new \DateTime((string) $preferredDate));
            }

            // Update name split
            $lastName = $request->request->get('lastName');
            if ($firstName !== null && $firstName !== '' && ($lastName === null || $lastName === '')) {
                $nameParts = explode(' ', (string) $firstName, 2);
                $booking->setFirstName($nameParts[0]);
                $booking->setLastName($nameParts[1] ?? '');
            } else {
                $booking->setFirstName((string) $firstName);
                $booking->setLastName((string) ($lastName ?? ''));
            }

            // Update session
            $sessionId = $request->request->get('session_id');
            if ($sessionId !== null && $sessionId !== '') {
                $session = $sessionRepository->find($sessionId);
                if ($session instanceof \App\Entity\Session) {
                    $booking->setSession($session);
                }
            }

            $booking->setUpdatedAt(new \DateTime());
            $bookingRepository->save($booking, true);

            $this->addFlash('success', 'Booking updated successfully!');

            return $this->redirectToRoute('booking_data_display');
        }

        return $this->render('Front-office/booking/edit.html.twig', [
            'booking' => $booking,
            'sessions' => $sessions,
            'categories' => $categories,
            'instructors' => $instructors,
            'students' => $students,
            'allUsers' => $userRepository->findAll(),
        ]);
    }

    #[Route('/api/bookings/time-slots/{sessionId}/{date}', name: 'api_booking_time_slots', methods: ['GET'])]
    public function getBookedTimeSlots(
        int $sessionId,
        string $date,
        BookingRepository $bookingRepository,
        SessionRepository $sessionRepository,
    ): JsonResponse {
        try {
            // Validate session exists
            $session = $sessionRepository->find($sessionId);
            if ($session === null) {
                return $this->json([
                    'success' => false,
                    'error' => 'Session not found'
                ], 404);
            }

            // Parse date and create date range for the entire day
            $startDate = \DateTime::createFromFormat('Y-m-d', $date);
            if ($startDate === false) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid date format'
                ], 400);
            }
            $startDate->setTime(0, 0, 0);
            
            $endDate = clone $startDate;
            $endDate->setTime(23, 59, 59);

            // Query bookings for this session and date
            $bookings = $bookingRepository->createQueryBuilder('b')
                ->where('b.session = :session')
                ->andWhere('b.preferredDate >= :startDate')
                ->andWhere('b.preferredDate <= :endDate')
                ->setParameter('session', $session)
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getResult();

            $bookedSlots = [];
            foreach ($bookings as $booking) {
                if ($booking->getStartTime() && $booking->getDurationMinutes()) {
                    $startTime = $booking->getStartTime()->format('H:i');
                    
                    // Calculate end time
                    $start = $booking->getStartTime();
                    $end = clone $start;
                    $end->modify('+' . $booking->getDurationMinutes() . ' minutes');
                    $endTime = $end->format('H:i');
                    
                    $bookedSlots[] = [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'duration_minutes' => $booking->getDurationMinutes(),
                        'status' => $booking->getStatus(),
                    ];
                }
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'bookedSlots' => $bookedSlots,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}

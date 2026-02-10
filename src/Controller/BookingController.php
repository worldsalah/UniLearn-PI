<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BookingRepository;
use App\Repository\SessionRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;

class BookingController extends AbstractController {
    #[Route('/booking', name: 'booking_create')]
    public function new(
        Request $request,
        BookingRepository $bookingRepository,
        SessionRepository $sessionRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository
    ): Response {
        // Get the session_id from query parameter if provided
        $selectedSessionId = $request->query->get('session_id');
        $selectedSession = null;
        
        if ($selectedSessionId) {
            $selectedSession = $sessionRepository->find($selectedSessionId);
        }
        
        //  Get all sessions BEFORE submit
        $sessions = $sessionRepository->findSessionsWithInstructorInfo();
        
        // Get all active categories
        $categories = $categoryRepository->findBy(['isActive' => true], ['name' => 'ASC']);
        
        // Get instructors (users with instructor role)
        $instructorRole = $roleRepository->findOneBy(['name' => 'instructor']);
        $instructors = [];
        if ($instructorRole) {
            $instructors = $userRepository->findBy(['role' => $instructorRole], ['fullName' => 'ASC']);
        } else {
            // Try alternative role name
            $instructorRole = $roleRepository->findOneBy(['name' => 'ROLE_INSTRUCTOR']);
            if ($instructorRole) {
                $instructors = $userRepository->findBy(['role' => $instructorRole], ['fullName' => 'ASC']);
            }
        }
        
        // Get students (users with student role)
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        $students = [];
        if ($studentRole) {
            $students = $userRepository->findBy(['role' => $studentRole], ['fullName' => 'ASC']);
        } else {
            // Try alternative role name
            $studentRole = $roleRepository->findOneBy(['name' => 'ROLE_STUDENT']);
            if ($studentRole) {
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
            
            if ($this->getUser() && $this->getUser()->getRole()->getName() === 'student') {
                // For students, instructor selection is only required if no session is pre-selected
                if (!$selectedSession) {
                    $instructorId = $request->request->get('instructor_id');
                    if (empty($instructorId)) {
                        $errors['instructor_id'] = 'Please select an instructor for your session.';
                    }
                }
                // If session is pre-selected, instructor will be assigned automatically
            } elseif ($this->getUser() && $this->getUser()->getRole()->getName() === 'instructor') {
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
            } elseif (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['userEmail'] = 'Please enter a valid email address.';
            }

            if (empty($preferredDate)) {
                $errors['preferred_date'] = 'Preferred date is required.';
            } else {
                $date = \DateTime::createFromFormat('Y-m-d', $preferredDate);
                if (!$date) {
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
                    'selectedSession' => $selectedSession
                ]);
            }

            // Process valid booking
            $booking = new Booking();

            // Set the user relationship if logged in
            if ($this->getUser()) {
                $user = $this->getUser();
                $booking->setUser($user);
                $booking->setFirstName($request->request->get('firstName') ?: $user->getFullName());
                $booking->setUserEmail($request->request->get('userEmail') ?: $user->getEmail());
            } else {
                $booking->setFirstName($request->request->get('firstName'));
                $booking->setUserEmail($request->request->get('userEmail'));
            }

            // Set preferred date
            $preferredDate = $request->request->get('preferred_date');
            if ($preferredDate) {
                $booking->setPreferredDate(new \DateTime($preferredDate));
            }

            // Set creation date
            $booking->setCreatedAt(new \DateTime());
            
            // Set default status
            $booking->setStatus('pending');

            // Set lastName (split from firstName if needed)
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName') ?: '';
            if ($firstName && !$lastName) {
                $nameParts = explode(' ', $firstName, 2);
                $booking->setFirstName($nameParts[0]);
                $booking->setLastName($nameParts[1] ?? '');
            } else {
                $booking->setFirstName($firstName);
                $booking->setLastName($lastName);
            }

            // Link the booking to the selected session
            $sessionId = $request->request->get('session_id');
            if ($sessionId) {
                $session = $sessionRepository->find($sessionId);
                if ($session) {
                    $booking->setSession($session);
                }
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
            'selectedSession' => $selectedSession
        ]);
    }

    #[Route('/bookings', name: 'all_bookings')]
    public function getAllBookings(BookingRepository $bookingRepository): Response
    {
        // Get all bookings using your repository method
        $bookings = $bookingRepository->findAllBookings();
        // Render the template and pass the bookings
        return $this->render('/Front-office/booking/bookingList.html.twig', [
            'bookings' => $bookings
        ]);
    }

    #[Route('/booking-data', name: 'booking_data_display')]
    public function displayBookingData(
        BookingRepository $bookingRepository
    ): Response {
        // Get all bookings
        $bookings = $bookingRepository->findAll();
        
        // Render the booking display template
        return $this->render('Front-office/booking/bookingDisplay.html.twig', [
            'bookings' => $bookings
        ]);
    }

    #[Route('/bookings/update', name: 'booking_update', methods: ['POST'])]
    public function update(
        Request $request,
        BookingRepository $bookingRepository
    ): Response {

        $booking = $bookingRepository->find($request->request->get('id'));

        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        $booking->setFirstName($request->request->get('firstName'));
        $booking->setLastName($request->request->get('lastName'));
        $booking->setUserEmail($request->request->get('userEmail'));
        $booking->setPhoneNumber($request->request->get('phoneNumber'));

        $bookingRepository->save($booking, true);

        return $this->redirectToRoute('all_bookings');
    }

    #[Route('/booking/{id}/accept', name: 'booking_accept', methods: ['POST'])]
    public function acceptBooking(
        int $id,
        BookingRepository $bookingRepository
    ): Response {
        $booking = $bookingRepository->find($id);
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        $booking->setStatus('accepted');
        $booking->setUpdatedAt(new \DateTime());
        $bookingRepository->save($booking, true);

        $this->addFlash('success', 'Booking has been accepted successfully!');
        return $this->redirectToRoute('all_bookings');
    }

    #[Route('/booking/{id}/deny', name: 'booking_deny', methods: ['POST'])]
    public function denyBooking(
        int $id,
        BookingRepository $bookingRepository
    ): Response {
        $booking = $bookingRepository->find($id);
        if (!$booking) {
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
        EntityManagerInterface $em
    ): Response {
        $booking = $bookingRepository->find($id);

        if (!$booking) {
            if ($request->isXmlHttpRequest()) {
                return new Response('Booking not found', 404);
            }
            throw $this->createNotFoundException();
        }

        // For AJAX requests, skip CSRF validation for simplicity
        // In production, you should implement proper CSRF protection for AJAX
        if ($request->isMethod('DELETE') || $this->isCsrfTokenValid('delete_booking_' . $id, $request->request->get('_token'))) {
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
        BookingRepository $bookingRepository
    ): Response {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        return $this->render('Front-office/booking/view.html.twig', [
            'booking' => $booking
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
        RoleRepository $roleRepository
    ): Response {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        // Get all sessions for the form
        $sessions = $sessionRepository->findSessionsWithInstructorInfo();
        
        // Get all active categories
        $categories = $categoryRepository->findBy(['isActive' => true], ['name' => 'ASC']);
        
        // Get instructors (users with instructor role)
        $instructorRole = $roleRepository->findOneBy(['name' => 'instructor']);
        $instructors = [];
        if ($instructorRole) {
            $instructors = $userRepository->findBy(['role' => $instructorRole], ['fullName' => 'ASC']);
        }
        
        // Get students (users with student role)
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        $students = [];
        if ($studentRole) {
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
            } elseif (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['userEmail'] = 'Please enter a valid email address.';
            }

            if (empty($preferredDate)) {
                $errors['preferred_date'] = 'Preferred date is required.';
            } else {
                $date = \DateTime::createFromFormat('Y-m-d', $preferredDate);
                if (!$date) {
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
                    'formData' => $request->request->all()
                ]);
            }

            // Update booking
            $booking->setFirstName($firstName);
            $booking->setUserEmail($userEmail);
            $booking->setPhoneNumber($request->request->get('phoneNumber'));
            
            // Set preferred date
            if ($preferredDate) {
                $booking->setPreferredDate(new \DateTime($preferredDate));
            }

            // Update name split
            $lastName = $request->request->get('lastName') ?: '';
            if ($firstName && !$lastName) {
                $nameParts = explode(' ', $firstName, 2);
                $booking->setFirstName($nameParts[0]);
                $booking->setLastName($nameParts[1] ?? '');
            } else {
                $booking->setFirstName($firstName);
                $booking->setLastName($lastName);
            }

            // Update session
            $sessionId = $request->request->get('session_id');
            if ($sessionId) {
                $session = $sessionRepository->find($sessionId);
                if ($session) {
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
            'allUsers' => $userRepository->findAll()
        ]);
    }
}

<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Repository\SessionRepository;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

class SessionController extends AbstractController {
    #[Route('/session', name: 'session_create')]
    public function new(
        Request $request,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker
    ): Response {
        // Check if user has permission to create sessions
        if (!$authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You must be logged in to create sessions.');
            return $this->redirectToRoute('app_login');
        }
        
        // Only instructors and admins can create sessions
        if (!$authChecker->isGranted('ROLE_INSTRUCTOR') && !$authChecker->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Only instructors and administrators can create sessions.');
            return $this->redirectToRoute('all_sessions');
        }
        if ($request->isMethod('POST')) {   
            $session = new Session();

            $session->setName($request->request->get('name'));

            $dateString = $request->request->get('date');

            if ($dateString) {
                $date = new \DateTime($dateString);
                $session->setDate($date);
            }
            $session->setDuration($request->request->get('duration'));
            $session->setSessionDescription($request->request->get('sessionDescription'));
            $session->setLevel($request->request->get('level'));

            $sessionRepository->save($session);

            return $this->redirectToRoute('all_sessions'); 
        }

        return $this->render('/Front-office/session/add-session.html.twig');
    }

    #[Route('/instructor/create-session', name: 'instructor_create_session')]
    public function instructorCreateSession(
        Request $request,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker
    ): Response {
        // Check if user has permission to create sessions
        if (!$authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You must be logged in to create sessions.');
            return $this->redirectToRoute('app_login');
        }
        
        // Only instructors and admins can create sessions
        if (!$authChecker->isGranted('ROLE_INSTRUCTOR') && !$authChecker->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Only instructors and administrators can create sessions.');
            return $this->redirectToRoute('instructor_sessions');
        }

        if ($request->isMethod('POST')) {   
            $errors = [];
            
            // Validate session name
            $name = trim($request->request->get('name'));
            if (empty($name)) {
                $errors['name'] = 'Session name is required.';
            } elseif (strlen($name) < 3) {
                $errors['name'] = 'Session name must be at least 3 characters long.';
            } elseif (strlen($name) > 255) {
                $errors['name'] = 'Session name cannot exceed 255 characters.';
            }

            // Validate level
            $level = $request->request->get('level');
            $validLevels = ['beginner', 'intermediate', 'advanced'];
            if (empty($level)) {
                $errors['level'] = 'Session level is required.';
            } elseif (!in_array($level, $validLevels)) {
                $errors['level'] = 'Invalid session level selected.';
            }

            // Validate duration
            $duration = $request->request->get('duration');
            if (empty($duration)) {
                $errors['duration'] = 'Session duration is required.';
            } elseif (!is_numeric($duration)) {
                $errors['duration'] = 'Duration must be a number.';
            } elseif ($duration < 15) {
                $errors['duration'] = 'Session duration must be at least 15 minutes.';
            } elseif ($duration > 480) {
                $errors['duration'] = 'Session duration cannot exceed 480 minutes (8 hours).';
            }

            // Validate date range
            $startDateString = $request->request->get('startDate');
            $endDateString = $request->request->get('endDate');

            if (empty($startDateString)) {
                $errors['startDate'] = 'Start date is required.';
            } else {
                try {
                    $startDate = new \DateTime($startDateString);
                    if ($startDate < new \DateTime('today')) {
                        $errors['startDate'] = 'Start date cannot be in the past.';
                    }
                } catch (\Exception $e) {
                    $errors['startDate'] = 'Invalid start date format.';
                }
            }

            if (empty($endDateString)) {
                $errors['endDate'] = 'End date is required.';
            } else {
                try {
                    $endDate = new \DateTime($endDateString);
                } catch (\Exception $e) {
                    $errors['endDate'] = 'Invalid end date format.';
                }
            }

            // Validate date range logic
            if (!isset($errors['startDate']) && !isset($errors['endDate'])) {
                $startDate = new \DateTime($startDateString);
                $endDate = new \DateTime($endDateString);
                
                if ($endDate < $startDate) {
                    $errors['endDate'] = 'End date must be after or equal to start date.';
                }
                
                $interval = $startDate->diff($endDate);
                if ($interval->days > 365) {
                    $errors['endDate'] = 'Session period cannot exceed 1 year.';
                }
            }

            // Validate description
            $description = trim($request->request->get('sessionDescription'));
            if (empty($description)) {
                $errors['sessionDescription'] = 'Session description is required.';
            } elseif (strlen($description) < 10) {
                $errors['sessionDescription'] = 'Description must be at least 10 characters long.';
            } elseif (strlen($description) > 1000) {
                $errors['sessionDescription'] = 'Description cannot exceed 1000 characters.';
            }

            // If there are validation errors, re-render the form with errors
            if (!empty($errors)) {
                // Mock statistics for instructor dashboard
                $totalCourses = 2;
                $totalStudents = 30;
                $averageRating = 4.2;
                
                return $this->render('instructor/create-session.html.twig', [
                    'totalCourses' => $totalCourses,
                    'totalStudents' => $totalStudents,
                    'averageRating' => $averageRating,
                    'errors' => $errors,
                    'formData' => $request->request->all()
                ]);
            }

            // If no errors, create the session
            $session = new Session();
            $session->setName($name);
            $session->setLevel($level);
            $session->setDuration((int)$duration);
            $session->setSessionDescription($description);

            $startDate = new \DateTime($startDateString);
            $endDate = new \DateTime($endDateString);
            $session->setStartDate($startDate);
            $session->setEndDate($endDate);

            // Keep the old date field for backward compatibility
            $session->setDate($startDate);

            $sessionRepository->save($session);

            $this->addFlash('success', 'Session created successfully!');
            return $this->redirectToRoute('instructor_sessions'); 
        }

        // Mock statistics for instructor dashboard
        $totalCourses = 2;
        $totalStudents = 30;
        $averageRating = 4.2;
        
        return $this->render('instructor/create-session.html.twig', [
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'averageRating' => $averageRating
        ]);
    }

    #[Route('/instructor/session/{id}/edit', name: 'instructor_edit_session')]
    public function editSession(
        int $id,
        Request $request,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker
    ): Response {
        // Check if user has permission to edit sessions
        if (!$authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You must be logged in to edit sessions.');
            return $this->redirectToRoute('app_login');
        }
        
        // Only instructors and admins can edit sessions
        if (!$authChecker->isGranted('ROLE_INSTRUCTOR') && !$authChecker->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Only instructors and administrators can edit sessions.');
            return $this->redirectToRoute('instructor_sessions');
        }

        $session = $sessionRepository->find($id);
        if (!$session) {
            $this->addFlash('error', 'Session not found.');
            return $this->redirectToRoute('instructor_sessions');
        }

        if ($request->isMethod('POST')) {   
            $errors = [];
            
            // Validate session name
            $name = trim($request->request->get('name'));
            if (empty($name)) {
                $errors['name'] = 'Session name is required.';
            } elseif (strlen($name) < 3) {
                $errors['name'] = 'Session name must be at least 3 characters long.';
            } elseif (strlen($name) > 255) {
                $errors['name'] = 'Session name cannot exceed 255 characters.';
            }

            // Validate level
            $level = $request->request->get('level');
            $validLevels = ['beginner', 'intermediate', 'advanced'];
            if (empty($level)) {
                $errors['level'] = 'Session level is required.';
            } elseif (!in_array($level, $validLevels)) {
                $errors['level'] = 'Invalid session level selected.';
            }

            // Validate duration
            $duration = $request->request->get('duration');
            if (empty($duration)) {
                $errors['duration'] = 'Session duration is required.';
            } elseif (!is_numeric($duration)) {
                $errors['duration'] = 'Duration must be a number.';
            } elseif ($duration < 15) {
                $errors['duration'] = 'Session duration must be at least 15 minutes.';
            } elseif ($duration > 480) {
                $errors['duration'] = 'Session duration cannot exceed 480 minutes (8 hours).';
            }

            // Validate date range
            $startDateString = $request->request->get('startDate');
            $endDateString = $request->request->get('endDate');

            if (empty($startDateString)) {
                $errors['startDate'] = 'Start date is required.';
            } else {
                try {
                    $startDate = new \DateTime($startDateString);
                    if ($startDate < new \DateTime('today')) {
                        $errors['startDate'] = 'Start date cannot be in the past.';
                    }
                } catch (\Exception $e) {
                    $errors['startDate'] = 'Invalid start date format.';
                }
            }

            if (empty($endDateString)) {
                $errors['endDate'] = 'End date is required.';
            } else {
                try {
                    $endDate = new \DateTime($endDateString);
                } catch (\Exception $e) {
                    $errors['endDate'] = 'Invalid end date format.';
                }
            }

            // Validate date range logic
            if (!isset($errors['startDate']) && !isset($errors['endDate'])) {
                $startDate = new \DateTime($startDateString);
                $endDate = new \DateTime($endDateString);
                
                if ($endDate < $startDate) {
                    $errors['endDate'] = 'End date must be after or equal to start date.';
                }
                
                $interval = $startDate->diff($endDate);
                if ($interval->days > 365) {
                    $errors['endDate'] = 'Session period cannot exceed 1 year.';
                }
            }

            // Validate description
            $description = trim($request->request->get('sessionDescription'));
            if (empty($description)) {
                $errors['sessionDescription'] = 'Session description is required.';
            } elseif (strlen($description) < 10) {
                $errors['sessionDescription'] = 'Description must be at least 10 characters long.';
            } elseif (strlen($description) > 1000) {
                $errors['sessionDescription'] = 'Description cannot exceed 1000 characters.';
            }

            // If there are validation errors, re-render the form with errors
            if (!empty($errors)) {
                // Mock statistics for instructor dashboard
                $totalCourses = 2;
                $totalStudents = 30;
                $averageRating = 4.2;
                
                return $this->render('instructor/edit-session.html.twig', [
                    'totalCourses' => $totalCourses,
                    'totalStudents' => $totalStudents,
                    'averageRating' => $averageRating,
                    'session' => $session,
                    'errors' => $errors,
                    'formData' => $request->request->all()
                ]);
            }

            // If no errors, update the session
            $session->setName($name);
            $session->setLevel($level);
            $session->setDuration((int)$duration);
            $session->setSessionDescription($description);

            $startDate = new \DateTime($startDateString);
            $endDate = new \DateTime($endDateString);
            $session->setStartDate($startDate);
            $session->setEndDate($endDate);

            // Keep the old date field for backward compatibility
            $session->setDate($startDate);

            $sessionRepository->save($session);

            $this->addFlash('success', 'Session updated successfully!');
            return $this->redirectToRoute('instructor_sessions'); 
        }

        // Mock statistics for instructor dashboard
        $totalCourses = 2;
        $totalStudents = 30;
        $averageRating = 4.2;
        
        return $this->render('instructor/edit-session.html.twig', [
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'averageRating' => $averageRating,
            'session' => $session
        ]);
    }

    #[Route('/instructor/session/{id}/view', name: 'instructor_view_session')]
    public function viewSession(
        int $id,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker
    ): Response {
        // Check if user has permission to view sessions
        if (!$authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You must be logged in to view sessions.');
            return $this->redirectToRoute('app_login');
        }
        
        // Only instructors and admins can view sessions
        if (!$authChecker->isGranted('ROLE_INSTRUCTOR') && !$authChecker->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Only instructors and administrators can view sessions.');
            return $this->redirectToRoute('instructor_sessions');
        }

        $session = $sessionRepository->find($id);
        if (!$session) {
            $this->addFlash('error', 'Session not found.');
            return $this->redirectToRoute('instructor_sessions');
        }

        // Mock statistics for instructor dashboard
        $totalCourses = 2;
        $totalStudents = 30;
        $averageRating = 4.2;
        
        return $this->render('instructor/view-session.html.twig', [
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'averageRating' => $averageRating,
            'session' => $session
        ]);
    }

    #[Route('/instructor/session/{id}/delete', name: 'instructor_delete_session')]
    public function deleteSession(
        int $id,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker,
        EntityManagerInterface $em
    ): Response {
        // Check if user has permission to delete sessions
        if (!$authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You must be logged in to delete sessions.');
            return $this->redirectToRoute('app_login');
        }
        
        // Only instructors and admins can delete sessions
        if (!$authChecker->isGranted('ROLE_INSTRUCTOR') && !$authChecker->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Only instructors and administrators can delete sessions.');
            return $this->redirectToRoute('instructor_sessions');
        }

        $session = $sessionRepository->find($id);
        if (!$session) {
            $this->addFlash('error', 'Session not found.');
            return $this->redirectToRoute('instructor_sessions');
        }

        // Delete the session
        $em->remove($session);
        $em->flush();

        $this->addFlash('success', 'Session deleted successfully!');
        return $this->redirectToRoute('instructor_sessions');
    }

    #[Route('/instructor/sessions', name: 'instructor_sessions')]
    public function instructorSessions(
        SessionRepository $sessionRepository,
        UserRepository $userRepository
    ): Response {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get all sessions (in a real app, you'd filter by instructor)
        $sessions = $sessionRepository->findAllSessions();
        
        // Mock statistics for instructor dashboard
        $totalCourses = 2;
        $totalStudents = 30;
        $averageRating = 4.2;
        
        return $this->render('instructor/sessions.html.twig', [
            'sessions' => $sessions,
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'averageRating' => $averageRating
        ]);
    }

    #[Route('/sessions', name: 'all_sessions')]
    public function getAllSessions(SessionRepository $sessionRepository): Response
    {
        // Get all sessions using your repository method
        $sessions = $sessionRepository->findAllSessions();
        // Render the template and pass the sessions
        return $this->render('/Front-office/session/sessionList.html.twig', [
            'sessions' => $sessions
        ]);
    }

    #[Route('/sessions-data', name: 'session_data_display')]
    public function displaySessionData(
        SessionRepository $sessionRepository
    ): Response {
        // Get all sessions with instructor information
        $sessions = $sessionRepository->findSessionsWithInstructorInfo();
        
        // Render the session display template
        return $this->render('Front-office/session/sessionDisplay.html.twig', [
            'sessions' => $sessions
        ]);
    }

    #[Route('/session/{id}/view', name: 'session_view')]
    public function viewSessionDetails(
        int $id,
        SessionRepository $sessionRepository
    ): Response {
        $session = $sessionRepository->find($id);
        
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        return $this->render('Front-office/session/view.html.twig', [
            'session' => $session
        ]);
    }

    #[Route('/teacher/sessions', name: 'teacher_session')]
    public function getTeacherSessions(SessionRepository $sessionRepository): Response
    {
        // Get all sessions using your repository method
        $sessions = $sessionRepository->findAllSessions();
        // Render the template and pass the sessions
        return $this->render('/Front-office/session/teacher-session-list.html.twig', [
            'sessions' => $sessions
        ]);
    }

    #[Route('/session/update', name: 'session_update', methods: ['POST'])]
    public function update(
        Request $request,
        SessionRepository $sessionRepository
    ): Response {

        $session = $sessionRepository->find($request->request->get('id'));

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        $session->setName($request->request->get('name'));
        $session->setLevel($request->request->get('level'));
        $dateString = $request->request->get('date');
        if ($dateString) {
            $session->setDate(new DateTimeImmutable($dateString));
        }
        $session->setSessionDescription($request->request->get('sessionDescription'));
        $session->setDuration($request->request->get('duration'));

        $sessionRepository->save($session, true);

        return $this->redirectToRoute('teacher_session');
    }

    #[Route('/api/session/{id}/booked-dates', name: 'api_session_booked_dates', methods: ['GET'])]
    public function getBookedDates(
        int $id,
        SessionRepository $sessionRepository,
        BookingRepository $bookingRepository
    ): Response {
        $session = $sessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        // Get all bookings for this session
        $bookings = $bookingRepository->findBy(['session' => $session]);
        
        // Extract the booked dates
        $bookedDates = [];
        foreach ($bookings as $booking) {
            $preferredDate = $booking->getPreferredDate();
            if ($preferredDate) {
                $bookedDates[] = $preferredDate->format('Y-m-d');
            }
        }

        // Session data processed

        return $this->json([
            'sessionId' => $id,
            'bookedDates' => array_unique($bookedDates)
        ]);
    }

    #[Route('/session/delete', name: 'session_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        SessionRepository $sessionRepository,
        EntityManagerInterface $em
    ): Response {
        $id = $request->request->get('id');
        $session = $sessionRepository->find($id);

        if (!$session) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_session_' . $id, $request->request->get('_token'))) {
            $em->remove($session);
            $em->flush();

            $this->addFlash('success', 'Session deleted successfully');
        }

        return $this->redirectToRoute('teacher_session');
    }

    #[Route('/api/session/{id}', name: 'api_session_get', methods: ['GET'])]
    public function getSessionData(
        int $id,
        SessionRepository $sessionRepository
    ): JsonResponse {
        $session = $sessionRepository->find($id);
        
        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        return $this->json([
            'id' => $session->getId(),
            'name' => $session->getName(),
            'level' => $session->getLevel(),
            'duration' => $session->getDuration(),
            'startDate' => $session->getStartDate() ? $session->getStartDate()->format('Y-m-d') : null,
            'endDate' => $session->getEndDate() ? $session->getEndDate()->format('Y-m-d') : null,
            'sessionDescription' => $session->getSessionDescription(),
            'instructor_id' => $session->getInstructor() ? $session->getInstructor()->getId() : null,
            'instructor' => $session->getInstructor() ? [
                'id' => $session->getInstructor()->getId(),
                'fullName' => $session->getInstructor()->getFullName(),
                'email' => $session->getInstructor()->getEmail()
            ] : null
        ]);
    }

    #[Route('/api/instructors', name: 'api_instructors', methods: ['GET'])]
    public function getInstructors(
        UserRepository $userRepository,
        RoleRepository $roleRepository
    ): JsonResponse {
        // Get instructor role
        $instructorRole = $roleRepository->findOneBy(['name' => 'instructor']);
        if (!$instructorRole) {
            $instructorRole = $roleRepository->findOneBy(['name' => 'ROLE_INSTRUCTOR']);
        }
        
        if (!$instructorRole) {
            return $this->json([]);
        }
        
        $instructors = $userRepository->findBy(['role' => $instructorRole]);
        
        $instructorData = [];
        foreach ($instructors as $instructor) {
            $instructorData[] = [
                'id' => $instructor->getId(),
                'fullName' => $instructor->getFullName(),
                'email' => $instructor->getEmail()
            ];
        }
        
        return $this->json($instructorData);
    }
}

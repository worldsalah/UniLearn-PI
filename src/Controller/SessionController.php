<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\BookingRepository;
use App\Repository\RoleRepository;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SessionController extends AbstractController
{
    #[Route('/session', name: 'session_create')]
    public function new(
        Request $request,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker,
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

            $name = $request->request->get('name');
            if (is_string($name)) {
                $session->setName($name);
            }

            $dateString = $request->request->get('date');

            if (is_string($dateString) && $dateString !== '') {
                $date = new \DateTime($dateString);
                $session->setDate($date);
            }
            $duration = $request->request->get('duration');
            if (is_numeric($duration)) {
                $session->setDuration((int) $duration);
            }
            $sessionDescription = $request->request->get('sessionDescription');
            $session->setSessionDescription(is_string($sessionDescription) ? $sessionDescription : null);
            $level = $request->request->get('level');
            $session->setLevel(is_string($level) ? $level : '');

            $sessionRepository->save($session);

            return $this->redirectToRoute('all_sessions');
        }

        return $this->render('/Front-office/session/add-session.html.twig');
    }

    #[Route('/instructor/create-session', name: 'instructor_create_session')]
    public function instructorCreateSession(
        Request $request,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker,
        CategoryRepository $categoryRepository,
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
            $nameRaw = $request->request->get('name');
            $name = is_string($nameRaw) ? trim($nameRaw) : '';
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
            } elseif (!in_array($level, $validLevels, true)) {
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

            // Validate availability time range
            $availableFromString = $request->request->get('availableFrom');
            $availableToString = $request->request->get('availableTo');

            if (empty($availableFromString)) {
                $errors['availableFrom'] = 'Available from time is required.';
            }

            if (empty($availableToString)) {
                $errors['availableTo'] = 'Available to time is required.';
            }

            if (!isset($errors['availableFrom']) && !isset($errors['availableTo'])) {
                try {
                    $availableFromStringSafe = is_string($availableFromString) ? $availableFromString : '';
                    $availableToStringSafe = is_string($availableToString) ? $availableToString : '';
                    $availableFrom = new \DateTimeImmutable($availableFromStringSafe);
                    $availableTo = new \DateTimeImmutable($availableToStringSafe);

                    if ($availableTo <= $availableFrom) {
                        $errors['availableTo'] = 'Available to time must be after available from time.';
                    }
                } catch (\Exception $e) {
                    $errors['availableFrom'] = 'Invalid time format.';
                }
            }

            // Validate hourly price
            $hourlyPrice = $request->request->get('hourlyPrice');
            if (empty($hourlyPrice)) {
                $errors['hourlyPrice'] = 'Price per hour is required.';
            } elseif (!is_numeric($hourlyPrice)) {
                $errors['hourlyPrice'] = 'Price per hour must be a number.';
            } elseif ((float) $hourlyPrice <= 0) {
                $errors['hourlyPrice'] = 'Price per hour must be greater than 0.';
            }

            // Validate date range
            $startDateString = $request->request->get('startDate');
            $endDateString = $request->request->get('endDate');
            $startDateStringSafe = '';
            $endDateStringSafe = '';

            if (empty($startDateString)) {
                $errors['startDate'] = 'Start date is required.';
            } else {
                $startDateStringSafe = is_string($startDateString) ? $startDateString : '';
                try {
                    $startDate = new \DateTime($startDateStringSafe);
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
                $endDateStringSafe = is_string($endDateString) ? $endDateString : '';
                try {
                    $endDate = new \DateTime($endDateStringSafe);
                } catch (\Exception $e) {
                    $errors['endDate'] = 'Invalid end date format.';
                }
            }

            // Validate date range logic
            if (!isset($errors['startDate']) && !isset($errors['endDate'])) {
                $startDate = new \DateTime($startDateStringSafe);
                $endDate = new \DateTime($endDateStringSafe);

                if ($endDate < $startDate) {
                    $errors['endDate'] = 'End date must be after or equal to start date.';
                }

                $interval = $startDate->diff($endDate);
                if ($interval->days > 365) {
                    $errors['endDate'] = 'Session period cannot exceed 1 year.';
                }
            }

            // Validate description
            $descriptionRaw = $request->request->get('sessionDescription');
            $description = is_string($descriptionRaw) ? trim($descriptionRaw) : '';
            if (empty($description)) {
                $errors['sessionDescription'] = 'Session description is required.';
            } elseif (strlen($description) < 10) {
                $errors['sessionDescription'] = 'Description must be at least 10 characters long.';
            } elseif (strlen($description) > 1000) {
                $errors['sessionDescription'] = 'Description cannot exceed 1000 characters.';
            }

            // Get categories from database for re-render
            $categories = $categoryRepository->findBy(['isActive' => true], ['name' => 'ASC']);

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
                    'categories' => $categories,
                    'errors' => $errors,
                    'formData' => $request->request->all(),
                ]);
            }

            // If no errors, create the session
            $session = new Session();
            $session->setName($name);
            $session->setLevel(is_string($level) ? $level : '');
            $session->setDuration((int) $duration);
            $session->setSessionDescription($description);

            // Associate current instructor
            $user = $this->getUser();
            if ($user instanceof \App\Entity\User) {
                $session->setInstructor($user);
            }

            $startDate = new \DateTime($startDateStringSafe);
            $endDate = new \DateTime($endDateStringSafe);
            $session->setStartDate($startDate);
            $session->setEndDate($endDate);

            // Keep the old date field for backward compatibility
            $session->setDate($startDate);

            // Set availability times (already validated above)
            $availableFromStringSafe = is_string($availableFromString) ? $availableFromString : '';
            $availableToStringSafe = is_string($availableToString) ? $availableToString : '';
            $session->setAvailableFrom(new \DateTimeImmutable($availableFromStringSafe));
            $session->setAvailableTo(new \DateTimeImmutable($availableToStringSafe));

            // Set hourly price (already validated above)
            $session->setHourlyPrice(number_format((float) $hourlyPrice, 2, '.', ''));

            // Set category if selected
            $categoryId = $request->request->get('category_id');
            if (!empty($categoryId)) {
                $category = $categoryRepository->find($categoryId);
                if ($category !== null) {
                    $session->setCategory($category);
                }
            }

            $sessionRepository->save($session);

            $this->addFlash('success', 'Session created successfully!');

            return $this->redirectToRoute('instructor_sessions');
        }

        // Get categories from database
        $categories = $categoryRepository->findBy(['isActive' => true], ['name' => 'ASC']);

        // Mock statistics for instructor dashboard
        $totalCourses = 2;
        $totalStudents = 30;
        $averageRating = 4.2;

        return $this->render('instructor/create-session.html.twig', [
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'averageRating' => $averageRating,
            'categories' => $categories,
        ]);
    }

    #[Route('/instructor/session/{id}/edit', name: 'instructor_edit_session')]
    public function editSession(
        int $id,
        Request $request,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker,
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
        if (!$session instanceof Session) {
            $this->addFlash('error', 'Session not found.');

            return $this->redirectToRoute('instructor_sessions');
        }

        if ($request->isMethod('POST')) {
            $errors = [];

            // Validate session name
            $nameRaw = $request->request->get('name');
            $name = is_string($nameRaw) ? trim($nameRaw) : '';
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
            } elseif (!in_array($level, $validLevels, true)) {
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

            // Get availability times (not validated in edit, but needed for update)
            $availableFromString = $request->request->get('availableFrom');
            $availableToString = $request->request->get('availableTo');

            // Get hourly price (not validated in edit, but needed for update)
            $hourlyPrice = $request->request->get('hourlyPrice');

            // Validate date range
            $startDateString = $request->request->get('startDate');
            $endDateString = $request->request->get('endDate');
            $startDateStringSafe = '';
            $endDateStringSafe = '';

            if (empty($startDateString)) {
                $errors['startDate'] = 'Start date is required.';
            } else {
                $startDateStringSafe = is_string($startDateString) ? $startDateString : '';
                try {
                    $startDate = new \DateTime($startDateStringSafe);
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
                $endDateStringSafe = is_string($endDateString) ? $endDateString : '';
                try {
                    $endDate = new \DateTime($endDateStringSafe);
                } catch (\Exception $e) {
                    $errors['endDate'] = 'Invalid end date format.';
                }
            }

            // Validate date range logic
            if (!isset($errors['startDate']) && !isset($errors['endDate'])) {
                $startDate = new \DateTime($startDateStringSafe);
                $endDate = new \DateTime($endDateStringSafe);

                if ($endDate < $startDate) {
                    $errors['endDate'] = 'End date must be after or equal to start date.';
                }

                $interval = $startDate->diff($endDate);
                if ($interval->days > 365) {
                    $errors['endDate'] = 'Session period cannot exceed 1 year.';
                }
            }

            // Validate description
            $descriptionRaw = $request->request->get('sessionDescription');
            $description = is_string($descriptionRaw) ? trim($descriptionRaw) : '';
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
                    'formData' => $request->request->all(),
                ]);
            }

            // If no errors, update the session
            $session->setName($name);
            $session->setLevel(is_string($level) ? $level : '');
            $session->setDuration((int) $duration);
            $session->setSessionDescription($description);

            $startDate = new \DateTime($startDateStringSafe);
            $endDate = new \DateTime($endDateStringSafe);
            $session->setStartDate($startDate);
            $session->setEndDate($endDate);

            // Keep the old date field for backward compatibility
            $session->setDate($startDate);

            // Set availability times (already validated above)
            $availableFromStringSafe = is_string($availableFromString) ? $availableFromString : '';
            $availableToStringSafe = is_string($availableToString) ? $availableToString : '';
            $session->setAvailableFrom(new \DateTimeImmutable($availableFromStringSafe));
            $session->setAvailableTo(new \DateTimeImmutable($availableToStringSafe));

            // Set hourly price (already validated above)
            $session->setHourlyPrice(number_format((float) $hourlyPrice, 2, '.', ''));

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
            'session' => $session,
        ]);
    }

    #[Route('/instructor/session/{id}/view', name: 'instructor_view_session')]
    public function viewSession(
        int $id,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker,
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
        if (!$session instanceof Session) {
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
            'session' => $session,
        ]);
    }

    #[Route('/instructor/session/{id}/delete', name: 'instructor_delete_session')]
    public function deleteSession(
        int $id,
        SessionRepository $sessionRepository,
        AuthorizationCheckerInterface $authChecker,
        EntityManagerInterface $em,
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
        if (!$session instanceof Session) {
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
        UserRepository $userRepository,
    ): Response {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
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
            'averageRating' => $averageRating,
        ]);
    }

    #[Route('/sessions', name: 'all_sessions')]
    public function getAllSessions(
        Request $request,
        SessionRepository $sessionRepository,
        CategoryRepository $categoryRepository
    ): Response {
        // Get query parameters
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $level = $request->query->get('level', '');
        $minPrice = $request->query->get('minPrice', '');
        $maxPrice = $request->query->get('maxPrice', '');
        $sort = $request->query->get('sort', 'date_asc');

        // Build query with filters
        $qb = $sessionRepository->createQueryBuilder('s')
            ->leftJoin('s.instructor', 'i')
            ->leftJoin('s.category', 'c')
            ->addSelect('i', 'c');

        // Search filter
        if (!empty($search)) {
            $qb->andWhere('s.name LIKE :search OR s.sessionDescription LIKE :search OR i.fullName LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Category filter
        if (!empty($category)) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', $category);
        }

        // Level filter
        if (!empty($level)) {
            $qb->andWhere('s.level = :level')
               ->setParameter('level', $level);
        }

        // Price range filter
        if (!empty($minPrice)) {
            $qb->andWhere('s.hourlyPrice >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }
        if (!empty($maxPrice)) {
            $qb->andWhere('s.hourlyPrice <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        // Sorting
        switch ($sort) {
            case 'price_asc':
                $qb->orderBy('s.hourlyPrice', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('s.hourlyPrice', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('s.startDate', 'ASC');
                break;
            case 'date_desc':
                $qb->orderBy('s.startDate', 'DESC');
                break;
            case 'name_asc':
                $qb->orderBy('s.name', 'ASC');
                break;
            case 'duration_asc':
                $qb->orderBy('s.duration', 'ASC');
                break;
            default:
                $qb->orderBy('s.startDate', 'ASC');
        }

        $sessions = $qb->getQuery()->getResult();

        // Get all categories for filter dropdown
        $categories = $categoryRepository->findBy(['isActive' => true], ['name' => 'ASC']);

        // Render the template and pass the sessions and filters
        return $this->render('/Front-office/session/sessionList.html.twig', [
            'sessions' => $sessions,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'level' => $level,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'sort' => $sort,
            ],
        ]);
    }

    #[Route('/sessions-data', name: 'session_data_display')]
    public function displaySessionData(
        SessionRepository $sessionRepository,
    ): Response {
        // Get all sessions with instructor information
        $sessions = $sessionRepository->findSessionsWithInstructorInfo();

        // Render the session display template
        return $this->render('Front-office/session/sessionDisplay.html.twig', [
            'sessions' => $sessions,
        ]);
    }

    #[Route('/session/{id}/view', name: 'session_view')]
    public function viewSessionDetails(
        int $id,
        SessionRepository $sessionRepository,
    ): Response {
        $session = $sessionRepository->find($id);

        if (!$session instanceof Session) {
            throw $this->createNotFoundException('Session not found');
        }

        return $this->render('Front-office/session/view.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/teacher/sessions', name: 'teacher_session')]
    public function getTeacherSessions(SessionRepository $sessionRepository): Response
    {
        // Get all sessions using your repository method
        $sessions = $sessionRepository->findAllSessions();

        // Render the template and pass the sessions
        return $this->render('/Front-office/session/teacher-session-list.html.twig', [
            'sessions' => $sessions,
        ]);
    }

    #[Route('/session/update', name: 'session_update', methods: ['POST'])]
    public function update(
        Request $request,
        SessionRepository $sessionRepository,
    ): Response {
        $session = $sessionRepository->find($request->request->get('id'));

        if (!$session instanceof Session) {
            throw $this->createNotFoundException('Session not found');
        }

        $name = $request->request->get('name');
        if (is_string($name)) {
            $session->setName($name);
        }
        $level = $request->request->get('level');
        if (is_string($level)) {
            $session->setLevel($level);
        }
        $dateString = $request->request->get('date');
        if (is_string($dateString) && $dateString !== '') {
            $session->setDate(new \DateTimeImmutable($dateString));
        }
        $sessionDescription = $request->request->get('sessionDescription');
        $session->setSessionDescription(is_string($sessionDescription) ? $sessionDescription : null);
        $duration = $request->request->get('duration');
        if (is_numeric($duration)) {
            $session->setDuration((int) $duration);
        }

        $sessionRepository->save($session, true);

        return $this->redirectToRoute('teacher_session');
    }

    #[Route('/api/session/{id}/booked-dates', name: 'api_session_booked_dates', methods: ['GET'])]
    public function getBookedDates(
        int $id,
        SessionRepository $sessionRepository,
        BookingRepository $bookingRepository,
    ): Response {
        $session = $sessionRepository->find($id);
        if (!$session instanceof Session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        // Get all bookings for this session
        $bookings = $bookingRepository->findBy(['session' => $session]);

        // Extract the booked dates
        $bookedDates = [];
        foreach ($bookings as $booking) {
            $preferredDate = $booking->getPreferredDate();
            if ($preferredDate !== null) {
                $bookedDates[] = $preferredDate->format('Y-m-d');
            }
        }

        // Debug logging
        error_log('DEBUG: Session ID: '.$id.' - Found '.count($bookings).' bookings');
        error_log('DEBUG: Booked dates: '.implode(', ', $bookedDates));

        return $this->json([
            'sessionId' => $id,
            'bookedDates' => array_unique($bookedDates),
        ]);
    }

    #[Route('/session/delete', name: 'session_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        SessionRepository $sessionRepository,
        EntityManagerInterface $em,
    ): Response {
        $id = $request->request->get('id');
        $session = $sessionRepository->find($id);

        if (!$session instanceof Session) {
            throw $this->createNotFoundException();
        }

        $token = $request->request->get('_token');
        $tokenString = is_string($token) ? $token : '';
        if ($this->isCsrfTokenValid('delete_session_'.$id, $tokenString)) {
            $em->remove($session);
            $em->flush();

            $this->addFlash('success', 'Session deleted successfully');
        }

        return $this->redirectToRoute('teacher_session');
    }

    #[Route('/api/session/{id}', name: 'api_session_get', methods: ['GET'])]
    public function getSessionData(
        int $id,
        SessionRepository $sessionRepository,
    ): JsonResponse {
        $session = $sessionRepository->find($id);

        if (!$session instanceof Session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        return $this->json([
            'id' => $session->getId(),
            'name' => $session->getName(),
            'level' => $session->getLevel(),
            'duration' => $session->getDuration(),
            'availableFrom' => $session->getAvailableFrom() !== null ? $session->getAvailableFrom()->format('H:i') : null,
            'availableTo' => $session->getAvailableTo() !== null ? $session->getAvailableTo()->format('H:i') : null,
            'hourlyPrice' => $session->getHourlyPrice(),
            'startDate' => $session->getStartDate() !== null ? $session->getStartDate()->format('Y-m-d') : null,
            'endDate' => $session->getEndDate() !== null ? $session->getEndDate()->format('Y-m-d') : null,
            'sessionDescription' => $session->getSessionDescription(),
            'instructor_id' => $session->getInstructor()?->getId() ?? null,
            'instructor' => $session->getInstructor() !== null ? [
                'id' => $session->getInstructor()->getId(),
                'fullName' => $session->getInstructor()->getFullName(),
                'email' => $session->getInstructor()->getEmail(),
            ] : null,
        ]);
    }

    #[Route('/api/instructors', name: 'api_instructors', methods: ['GET'])]
    public function getInstructors(
        UserRepository $userRepository,
        RoleRepository $roleRepository,
    ): JsonResponse {
        // Get instructor role
        $instructorRole = $roleRepository->findOneBy(['name' => 'ROLE_INSTRUCTOR']);

        if ($instructorRole === null) {
            return $this->json([]);
        }

        $instructors = $userRepository->findBy(['role' => $instructorRole]);

        $instructorData = [];
        foreach ($instructors as $instructor) {
            $instructorData[] = [
                'id' => $instructor->getId(),
                'fullName' => $instructor->getFullName(),
                'email' => $instructor->getEmail(),
            ];
        }

        return $this->json($instructorData);
    }
}

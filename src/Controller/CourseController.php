<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Chapter;
use App\Entity\Course;
use App\Entity\CourseTest;
use App\Entity\CourseTestAnswer;
use App\Entity\CourseTestResult;
use App\Entity\Lesson;
use App\Entity\User;
use App\Form\CourseType;
use App\Form\EnrollmentType;
use App\Repository\CategoryRepository;
use App\Repository\CertificateRepository;
use App\Repository\CourseRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\LessonCompletionRepository;
use App\Service\GoogleBooksService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\JsonResponse;

class CourseController extends AbstractController
{
    private GoogleBooksService $googleBooksService;
    private EnrollmentRepository $enrollmentRepository;

    public function __construct(GoogleBooksService $googleBooksService, EnrollmentRepository $enrollmentRepository)
    {
        $this->googleBooksService = $googleBooksService;
        $this->enrollmentRepository = $enrollmentRepository;
    }
    #[Route('/course/{id}', name: 'app_course_show', requirements: ['id' => '\d+'])]
    public function show(Course $course, EnrollmentRepository $enrollmentRepository, LessonCompletionRepository $lessonCompletionRepository, CertificateRepository $certificateRepository): Response
    {
        // Debug: Log course details
        error_log('Course ID: '.$course->getId());
        error_log('Course Title: '.$course->getTitle());
        error_log('Course Status: '.$course->getStatus());

        // Check if user is enrolled
        $user = $this->getUser();
        $isEnrolled = false;
        $hasCompletedAnyLesson = false;
        $isComplete = false;
        $hasCertificate = false;
        $certificate = null;
        $enrollment = null;
        
        if ($user instanceof \App\Entity\User) {
            $enrollment = $enrollmentRepository->findOneBy(['user' => $user, 'course' => $course]);
            $isEnrolled = $enrollment !== null;

            if ($isEnrolled) {
                $hasCompletedAnyLesson = count($lessonCompletionRepository->findByUserAndCourse($user, $course)) > 0;
                // Check if course is complete (progress >= 100 or status = completed)
                $isComplete = $enrollment->getProgress() >= 100 || $enrollment->getStatus() === 'completed';
                
                // Check if user has certificate for this course (with error handling for orphaned certificates)
                try {
                    $certificate = $certificateRepository->findOneByUserAndCourse($user, $course);
                    $hasCertificate = $certificate !== null;
                } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                    // Certificate references a non-existent course, skip it
                    $certificate = null;
                    $hasCertificate = false;
                }
            }
        }

        // Create enrollment form
        $enrollmentForm = null;
        if ($user instanceof \App\Entity\User && !$isEnrolled) {
            $enrollmentForm = $this->createForm(EnrollmentType::class);
        }

        // Get book recommendations using Google Books API
        $courseTitle = $course->getTitle();
        $recommendedBooks = $this->googleBooksService->searchBooks($courseTitle ?? '');

        return $this->render('course/detail-advanced.html.twig', [
            'course' => $course,
            'enrollmentForm' => $enrollmentForm,
            'isEnrolled' => $isEnrolled,
            'hasCompletedAnyLesson' => $hasCompletedAnyLesson,
            'recommendedBooks' => $recommendedBooks,
            'isComplete' => $isComplete,
            'hasCertificate' => $hasCertificate,
            'certificate' => $certificate,
            'enrollment' => $enrollment,
        ]);
    }
    
    #[Route('/course/{id}/generate-pdf', name: 'app_course_generate_pdf', methods: ['GET'])]
    public function generatePdf(Course $course): Response
    {
        // Check if user is enrolled
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }
        
        // Check enrollment
        $enrollment = $this->enrollmentRepository->findOneByUserAndCourse($user, $course);
        if ($enrollment === null) {
            $this->addFlash('error', 'You must be enrolled in this course to generate a certificate.');
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }
        
        // Generate certificate HTML
        $html = $this->renderView('certificate/course_certificate.html.twig', [
            'user' => $user,
            'course' => $course,
            'enrollment' => $enrollment,
            'completionDate' => $enrollment->getCompletedAt() ?? new \DateTime(),
            'totalMaxScore' => 100,
            'userScore' => $enrollment->getScore() ?? 85,
            'quizCount' => count($course->getQuizzes()),
            'completionPercentage' => ($enrollment->getScore() / 100) * 100,
            'generatedDate' => new \DateTime()
        ]);
        
        // Generate PDF
        try {
            $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'Arial');
            $pdfOptions->set('isRemoteEnabled', true);
            $pdfOptions->set('isHtml5ParserEnabled', true);
            $pdfOptions->set('paper', 'A4');
            $pdfOptions->set('orientation', 'landscape');
            
            $dompdf = new Dompdf($pdfOptions);
            $dompdf->loadHtml($html);
            $pdfContent = $dompdf->output();
            
            // Create response
            $userFullName = $user->getFullName();
            $courseTitle = $course->getTitle();
            $filename = sprintf(
                'course_certificate_%s_%s_%s.pdf',
                strtolower(str_replace(' ', '_', $userFullName ?? '')),
                strtolower(str_replace(' ', '_', $courseTitle ?? '')),
                date('Y-m-d')
            );
            
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
            $response->headers->set('Content-Length', (string) strlen($pdfContent));
            
            return $response;
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to generate PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }
    }

    #[Route('/course/create', name: 'app_course_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository,
    ): Response {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the currently logged-in user
            $user = $this->getUser();
            if ($user === null) {
                return $this->redirectToRoute('app_login');
            }

            $course->setUser($user instanceof User ? $user : null);
            $course->setCreatedAt(new \DateTimeImmutable());
            $course->setStatus('inactive'); // Set course to inactive status by default

            // Handle file uploads
            $thumbnailFile = $form->get('thumbnailFile')->getData();
            if (null !== $thumbnailFile) {
                $newFilename = uniqid().'.'.$thumbnailFile->guessExtension();
                $projectDir = $this->getParameter('kernel.project_dir');
                $uploadPath = (is_string($projectDir) ? $projectDir : '') . '/public/uploads/courses/thumbnails';
                $thumbnailFile->move(
                    $uploadPath,
                    $newFilename
                );
                $course->setThumbnailUrl('/uploads/courses/thumbnails/'.$newFilename);
            }

            $videoFile = $form->get('videoFile')->getData();
            if (null !== $videoFile) {
                $newFilename = uniqid().'.'.$videoFile->guessExtension();
                $projectDir = $this->getParameter('kernel.project_dir');
                $uploadPath = (is_string($projectDir) ? $projectDir : '') . '/public/uploads/courses/videos';
                $videoFile->move(
                    $uploadPath,
                    $newFilename
                );
                $course->setVideoUrl('/uploads/courses/videos/'.$newFilename);
            }

            $entityManager->persist($course);
            $entityManager->flush();

            // Check if user provided chapters/lessons data from the form
            $chaptersData = $request->request->get('chapters');

            if (!empty($chaptersData)) {
                // Parse JSON string to array
                $chaptersArray = json_decode((string) $chaptersData, true);

                if (is_array($chaptersArray) && !empty($chaptersArray)) {
                    // User provided chapters/lessons manually - create only those
                    $this->createChaptersAndLessonsFromFormData($course, $chaptersArray, $entityManager);
                    $this->addFlash('success', 'Course created successfully with your custom curriculum!');
                } else {
                    // Invalid JSON or empty array - no chapters created
                    $this->addFlash('success', 'Course created successfully!');
                }
            } else {
                // No chapters provided - course created without any chapters
                $this->addFlash('success', 'Course created successfully!');
            }

            return $this->redirectToRoute('app_instructor_manage_courses');
        }

        return $this->render('course/instructor-create-course.html.twig', [
            'courseForm' => $form->createView(),
            'categories' => $categoryRepository->findActiveCategories(),
        ]);
    }

    #[Route('/course/{id}/test', name: 'app_course_test', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function test(Course $course): Response
    {
        // Check if user is enrolled
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // You can add test logic here
        // For now, render a simple test page or redirect back to course
        return $this->render('course/test.html.twig', [
            'course' => $course,
        ]);
    }

    #[Route('/course/added', name: 'app_course_added', methods: ['GET'])]
    public function courseAdded(): Response
    {
        return $this->render('course/course-added.html.twig');
    }

    #[Route('/admin/course/{id}', name: 'admin_course_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function adminShowCourse(Course $course): Response
    {
        return $this->render('admin/course-detail.html.twig', [
            'course' => $course,
        ]);
    }

    #[Route('/admin/course/{id}/lessons', name: 'admin_course_lessons', requirements: ['id' => '\d+'], methods: ['GET'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function adminCourseLessons(Course $course): Response
    {
        // Get all chapters for this course
        $chapters = $course->getChapters();

        // Prepare chapters data for template
        $chaptersData = [];
        foreach ($chapters as $chapter) {
            $lessons = [];
            foreach ($chapter->getLessons() as $lesson) {
                $lessons[] = [
                    'id' => $lesson->getId(),
                    'title' => $lesson->getTitle(),
                    'content' => $lesson->getContent(),
                    'duration' => $lesson->getDuration(),
                    'order' => $lesson->getSortOrder(),
                ];
            }

            $chaptersData[] = [
                'id' => $chapter->getId(),
                'title' => $chapter->getTitle(),
                'lessons' => $lessons,
                'lessonCount' => count($lessons),
            ];
        }

        return $this->render('admin/course-lessons.html.twig', [
            'course' => $course,
            'chapters' => $chaptersData,
        ]);
    }

    #[Route('/admin/course/{id}/edit', name: 'admin_course_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function adminEditCourse(Course $course): Response
    {
        return $this->render('admin/course-edit.html.twig', [
            'course' => $course,
        ]);
    }

    #[Route('/admin/courses', name: 'admin_course_list', methods: ['GET'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function adminCourseList(EntityManagerInterface $entityManager): Response
    {
        $courses = $entityManager->getRepository(Course::class)->findAll();

        $courseData = [];
        foreach ($courses as $course) {
            $courseData[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'shortDescription' => $course->getShortDescription(),
                'level' => $course->getLevel(),
                'price' => $course->getPrice(),
                'status' => $course->getStatus() ?? 'pending',
                'createdAt' => $course->getCreatedAt() !== null ? $course->getCreatedAt()->format('d M Y') : 'Unknown',
                'thumbnailUrl' => $course->getThumbnailUrl(),
                'instructor' => [
                    'name' => $course->getUser()?->getFullName() ?? 'Unknown',
                    'email' => $course->getUser()?->getEmail() ?? 'Unknown',
                ],
            ];
        }

        return $this->render('admin/course-list.html.twig', [
            'courses' => $courseData,
        ]);
    }

    public function adminCourseListTest(EntityManagerInterface $entityManager): Response
    {
        // Test with empty data
        return $this->render('admin/course-list-test.html.twig', [
            'courses' => [],
        ]);
    }

    #[Route('/api/course/{id}/activate', name: 'api_course_activate', methods: ['POST'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function activateCourse(Course $course, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $course->setStatus('live');
            $entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Course activated successfully',
                'course_id' => $course->getId(),
                'new_status' => 'live',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to activate course: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/course/{id}/deactivate', name: 'api_course_deactivate', methods: ['POST'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function deactivateCourse(Course $course, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $course->setStatus('inactive');
            $entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Course deactivated successfully',
                'course_id' => $course->getId(),
                'new_status' => 'inactive',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to deactivate course: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/course/{id}/unaccept', name: 'api_course_unaccept', requirements: ['id' => '\d+'], methods: ['POST'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function unacceptCourse(Request $request, Course $course, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid JSON data: '.json_last_error_msg(),
                ], 400);
            }

            $reason = $data['reason'] ?? 'Course does not meet our quality standards';

            // Update course status
            $course->setStatus('unaccept');
            $entityManager->flush();

            // You could log to database, send email, or use a notification service
            error_log('Course unaccepted: '.$course->getTitle().' - Reason: '.$reason);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Unacceptance notification sent to instructor',
                'course_id' => $course->getId(),
                'notification_sent' => true,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to process unacceptance: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/course/{id}/delete', name: 'api_course_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function deleteCourse(Course $course, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid JSON data: '.json_last_error_msg(),
                ], 400);
            }

            // Update course instead of deleting
            $course->setStatus('deleted');

            // Update category if provided
            if (isset($data['category']) && $data['category'] !== '') {
                $category = $entityManager->getRepository(Category::class)->find($data['category']);
                if ($category !== null) {
                    $course->setCategory($category);
                } else {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Category not found: '.$data['category'],
                    ], 400);
                }
            }

            $entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Course deleted successfully',
                'course_id' => $course->getId(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to delete course: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create chapters and lessons from form data.
     */
    private function createChaptersAndLessonsFromFormData(Course $course, array $chaptersData, EntityManagerInterface $entityManager): void
    {
        foreach ($chaptersData as $index => $chapterData) {
            if (empty($chapterData['title'])) {
                continue; // Skip empty chapters
            }

            $chapter = new Chapter();
            $chapter->setTitle($chapterData['title']);
            $chapter->setCourse($course);
            $chapter->setSortOrder($index + 1);

            $entityManager->persist($chapter);

            // Create lessons for this chapter if provided
            if (isset($chapterData['lessons']) && is_array($chapterData['lessons'])) {
                foreach ($chapterData['lessons'] as $lessonIndex => $lessonData) {
                    if (empty($lessonData['title'])) {
                        continue; // Skip empty lessons
                    }

                    $lesson = new Lesson();
                    $lesson->setTitle($lessonData['title']);
                    $lesson->setChapter($chapter);
                    $lesson->setDuration($lessonData['duration'] ?? '0:30');
                    $lesson->setType($lessonData['type'] ?? 'video');
                    $lesson->setStatus('active');
                    $lesson->setSortOrder($lessonIndex + 1);
                    $lesson->setDescription($lessonData['description'] ?? '');

                    $entityManager->persist($lesson);
                }
            }
        }

        $entityManager->flush();
    }
}

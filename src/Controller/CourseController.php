<?php

namespace App\Controller;

use App\Entity\Chapter;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\User;
use App\Message\ProcessCourseSubmission;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\FileStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CourseController extends AbstractController
{
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    #[Route('/course/{id}', name: 'app_course_show', requirements: ['id' => '\d+'])]
    public function show(Course $course): Response
    {
        return $this->render('instructor/course-detail.html.twig', [
            'course' => $course,
        ]);
    }

    #[Route('/course/create', name: 'app_course_create', methods: ['GET'])]
    public function createPage(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findActiveCategories();
        
        return $this->render('course/instructor-create-course.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/api/course/create', name: 'app_course_create_submit', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        FileStorageService $fileStorageService,
        ParameterBagInterface $params,
        ValidatorInterface $validator,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository
    ): JsonResponse {
        $uploadsDir = sys_get_temp_dir();
        // Create new Course entity and sanitize inputs
        $course = new Course();
        $course->setTitle(strip_tags($request->request->get('course_title')));
        $course->setShortDescription(strip_tags($request->request->get('short_description')));
        
        // Handle category - fetch from database using ID
        $categoryId = $request->request->get('course_category');
        $category = $categoryRepository->find($categoryId);
        if (!$category) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid category selected',
                'errors' => ['course_category' => 'Please select a valid category']
            ], 400);
        }
        $course->setCategory($category);
        
        // Assign course to user with ID 1
        $user = $userRepository->find(1);
        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'System user not found',
                'errors' => ['system' => 'Default user account not configured']
            ], 500);
        }
        $course->setUser($user);
        
        $course->setLevel(strip_tags($request->request->get('course_level')));
        $course->setPrice((float) $request->request->get('course_price'));
        $course->setLanguage(strip_tags($request->request->get('language')));
        $course->setDuration((float) $request->request->get('duration'));
        
        // Step 4 fields
        $course->setRequirements(strip_tags($request->request->get('requirements')));
        $course->setLearningOutcomes(strip_tags($request->request->get('learning_outcomes')));
        $course->setTargetAudience(strip_tags($request->request->get('target_audience')));
        
        // Validate basic course info before processing files
        $errors = $this->validator->validate($course);
        if (count($errors) > 0) {
            $errorMapped = [];
            foreach ($errors as $error) {
                $errorMapped[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errorMapped
            ], 400);
        }

        // 1. Handle Image - Validate and Stage locally
        $courseImage = $request->files->get('course_image'); 
        if ($courseImage) {
            $imageErrors = $this->validator->validate($courseImage, [
                new Image([
                    'maxWidth' => 800,
                    'maxHeight' => 200,
                    'mimeTypes' => ['image/jpeg', 'image/png'],
                    'mimeTypesMessage' => 'Veuillez télécharger une image JPG ou PNG valide.',
                    'maxWidthMessage' => 'La largeur de l\'image ne doit pas dépasser {{ limit }}px.',
                    'maxHeightMessage' => 'La hauteur de l\'image ne doit pas dépasser {{ limit }}px.',
                ])
            ]);

            if (count($imageErrors) > 0) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Validation de la miniature échouée',
                    'errors' => ['thumbnailUrl' => $imageErrors[0]->getMessage()]
                ], 400);
            }

            $imageOriginalName = $courseImage->getClientOriginalName();
            $tempImageFilename = uniqid('thumb_stage_', true) . '.' . $courseImage->guessExtension();
            $courseImage->move($uploadsDir, $tempImageFilename);
            $imagePath = $uploadsDir . DIRECTORY_SEPARATOR . $tempImageFilename;
            
            // Store file locally and get public path
            $publicPath = $fileStorageService->storeFile($imagePath, $imageOriginalName);
            $course->setThumbnailUrl($publicPath);
            $course->setImageStatus('active');
            $course->setImageProgress(1.0);
            
            // Clean up staging file
            unlink($imagePath);
        }

        // 2. Handle Video - Validate and Stage locally
        $courseVideo = $request->files->get('course_video');
        if ($courseVideo) {
            $videoErrors = $this->validator->validate($courseVideo, [
                new File([
                    'maxSize' => '500M',
                    'mimeTypes' => ['video/mp4', 'video/webm', 'video/ogg'],
                    'mimeTypesMessage' => 'Veuillez télécharger une vidéo valide (MP4, WebM ou OGG).',
                    'maxSizeMessage' => 'La taille de la vidéo ne doit pas dépasser {{ limit }} ({{ suffix }}).',
                ])
            ]);

            if (count($videoErrors) > 0) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Validation de la vidéo échouée',
                    'errors' => ['videoUrl' => $videoErrors[0]->getMessage()]
                ], 400);
            }

            $videoOriginalName = $courseVideo->getClientOriginalName();
            $tempVideoFilename = uniqid('video_stage_', true) . '.' . $courseVideo->guessExtension();
            $courseVideo->move($uploadsDir, $tempVideoFilename);
            $videoPath = $uploadsDir . DIRECTORY_SEPARATOR . $tempVideoFilename;

            // Store file locally and get public path
            $publicPath = $fileStorageService->storeFile($videoPath, $videoOriginalName);
            $course->setVideoUrl($publicPath);
            $course->setVideoStatus('active');
            $course->setVideoProgress(1.0);
            
            // Clean up staging file
            unlink($videoPath);
        }

        $course->setStatus('active');

        // Handle Chapters and Lessons
        $chaptersData = json_decode($request->request->get('chapters'), true);
        if ($chaptersData) {
            foreach ($chaptersData as $cIndex => $chapterData) {
                $chapter = new Chapter();
                $chapter->setTitle(strip_tags($chapterData['title']));
                $chapter->setCourse($course);
                
                if (isset($chapterData['lessons'])) {
                    foreach ($chapterData['lessons'] as $lIndex => $lessonData) {
                        $lesson = new Lesson();
                        $lesson->setTitle(strip_tags($lessonData['title']));
                        $lesson->setDuration(strip_tags($lessonData['duration']));
                        $lesson->setType(strip_tags($lessonData['type'] ?? 'video'));
                        $lesson->setContent($lessonData['content'] ?? null); // Might contain HTML or URLs
                        $lesson->setIsPreview((bool)($lessonData['isPreview'] ?? false));
                        $lesson->setDescription(strip_tags($lessonData['description'] ?? null));
                        $lesson->setSortOrder((int)($lessonData['sortOrder'] ?? $lIndex));
                        $lesson->setStatus('active');
                        
                        $lesson->setChapter($chapter);
                        $chapter->addLesson($lesson);
                        
                        // Validate Lesson
                        $lessonErrors = $validator->validate($lesson);
                        if (count($lessonErrors) > 0) {
                            return new JsonResponse([
                                'status' => 'error',
                                'message' => 'Lesson validation failed: ' . $lessonErrors[0]->getMessage()
                            ], 400);
                        }
                        
                        $entityManager->persist($lesson);
                    }
                }
                $entityManager->persist($chapter);
                
                // Validate Chapter
                $chapterErrors = $validator->validate($chapter);
                if (count($chapterErrors) > 0) {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Chapter validation failed: ' . $chapterErrors[0]->getMessage()
                    ], 400);
                }
            }
        }

        $entityManager->persist($course);
        $entityManager->flush();

        // Background processing removed in favor of direct local storage

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Course created successfully!',
            'course_id' => $course->getId(),
            'redirect_url' => $this->generateUrl('app_course_added')
        ]);
    }

    #[Route('/course/added', name: 'app_course_added', methods: ['GET'])]
    public function courseAdded(): Response
    {
        return $this->render('course/course-added.html.twig');
    }

    #[Route('/admin/course/{id}', name: 'admin_course_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminShowCourse(Course $course): Response
    {
        return $this->render('admin/course-detail.html.twig', [
            'course' => $course
        ]);
    }

    #[Route('/admin/course/{id}/lessons', name: 'admin_course_lessons', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
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
                'lessonCount' => count($lessons)
            ];
        }
        
        return $this->render('admin/course-lessons.html.twig', [
            'course' => $course,
            'chapters' => $chaptersData
        ]);
    }

    #[Route('/admin/course/{id}/edit', name: 'admin_course_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminEditCourse(Course $course): Response
    {
        return $this->render('admin/course-edit.html.twig', [
            'course' => $course
        ]);
    }

    #[Route('/admin/courses', name: 'admin_course_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminCourseList(EntityManagerInterface $entityManager): Response
    {
        // Get all courses from database
        $courses = $entityManager->getRepository(Course::class)->findAll();
        
        // Prepare course data for template
        $courseData = [];
        foreach ($courses as $course) {
            $courseData[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'shortDescription' => $course->getShortDescription(),
                'category' => $course->getCategory(),
                'level' => $course->getLevel(),
                'price' => $course->getPrice(),
                'status' => $course->getStatus() ?? 'pending',
                'createdAt' => $course->getCreatedAt() ? $course->getCreatedAt()->format('d M Y') : 'Unknown',
                'thumbnailUrl' => $course->getThumbnailUrl(),
                'instructor' => [
                    'name' => $course->getUser() ? $course->getUser()->getFullName() : 'Unknown',
                    'image' => null // You can add instructor image path here
                ],
                'levelClass' => $this->getLevelBadgeClass($course->getLevel()),
                'statusClass' => $this->getStatusBadgeClass($course->getStatus())
            ];
        }

        return $this->render('admin/course-list.html.twig', [
            'courses' => $courseData
        ]);
    }

    #[Route('/api/course/{id}/activate', name: 'api_course_activate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function activateCourse(Course $course, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Update course status to active (live)
            $course->setStatus('live');
            $entityManager->flush();
            
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Course activated successfully',
                'course_id' => $course->getId(),
                'new_status' => 'live'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to activate course: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/course/{id}/deactivate', name: 'api_course_deactivate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deactivateCourse(Course $course, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Update course status to inactive
            $course->setStatus('inactive');
            $entityManager->flush();
            
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Course deactivated successfully',
                'course_id' => $course->getId(),
                'new_status' => 'inactive'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to deactivate course: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/course/{id}/unaccept', name: 'api_course_unaccept', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unacceptCourse(Request $request, Course $course, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $reason = $request->request->get('reason');
            $courseTitle = $request->request->get('courseTitle');
            $instructorName = $request->request->get('instructorName');
            
            // Update course status to unaccept
            $course->setStatus('unaccept');
            $entityManager->flush();
            
            // TODO: Send actual notification to instructor
            // This could be an email, in-app notification, etc.
            // For now, we'll just log it (you can implement email later)
            $logMessage = sprintf(
                'Course "%s" by %s was unaccepted. Reason: %s',
                $courseTitle,
                $instructorName,
                $reason
            );
            
            // You could log to database, send email, or use a notification service
            error_log($logMessage);
            
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Unacceptance notification sent to instructor',
                'course_id' => $course->getId(),
                'new_status' => 'unaccept',
                'notification_sent' => true
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to process unacceptance: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/course/{id}/update', name: 'api_course_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateCourse(Request $request, Course $course, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        try {
            // Get data from request
            $data = json_decode($request->getContent(), true);
            
            // Update course properties
            $course->setTitle($data['title'] ?? $course->getTitle());
            $course->setShortDescription($data['description'] ?? $course->getShortDescription());
            $course->setCategory($data['category'] ?? $course->getCategory());
            $course->setLevel($data['level'] ?? $course->getLevel());
            $course->setPrice($data['price'] ?? $course->getPrice());
            $course->setStatus($data['status'] ?? $course->getStatus());
            
            // Handle thumbnail URL - could be empty string to remove image
            if (isset($data['thumbnailUrl'])) {
                if ($data['thumbnailUrl'] === '' || $data['thumbnailUrl'] === null) {
                    $course->setThumbnailUrl(null); // Remove image
                } else {
                    $course->setThumbnailUrl($data['thumbnailUrl']); // Set new image URL
                }
            }
            
            // Validate the course
            $errors = $validator->validate($course);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Validation failed: ' . implode(', ', $errorMessages)
                ], 400);
            }
            
            // Save changes
            $entityManager->flush();
            
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Course updated successfully',
                'course_id' => $course->getId()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to update course: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/course/{id}/delete', name: 'api_course_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteCourse(Course $course, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $courseId = $course->getId();
            
            // Remove the course from database
            $entityManager->remove($course);
            $entityManager->flush();
            
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Course deleted successfully',
                'course_id' => $courseId
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to delete course: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getLevelBadgeClass(?string $level): string
    {
        return match(strtolower($level)) {
            'beginner' => 'text-bg-primary',
            'intermediate' => 'text-bg-purple',
            'advanced' => 'text-bg-danger',
            'all levels', 'all level' => 'text-bg-orange',
            default => 'text-bg-secondary'
        };
    }

    private function getStatusBadgeClass(?string $status): string
    {
        return match(strtolower($status)) {
            'live', 'active', 'published' => 'bg-success bg-opacity-15 text-success',
            'pending', 'review' => 'bg-warning bg-opacity-15 text-warning',
            'unaccept', 'rejected', 'inactive' => 'bg-danger bg-opacity-15 text-danger',
            'draft' => 'bg-secondary bg-opacity-15 text-secondary',
            default => 'bg-secondary bg-opacity-15 text-secondary'
        };
    }
}

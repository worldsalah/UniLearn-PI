<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Service\YouTubeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class LessonVideoController extends AbstractController
{
    private LessonRepository $lessonRepository;
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(
        LessonRepository $lessonRepository,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        YouTubeService $youTubeService,
    ) {
        $this->lessonRepository = $lessonRepository;
        $this->courseRepository = $courseRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->youTubeService = $youTubeService;
    }

    /**
     * Add YouTube video to lesson.
     */
    #[Route('/api/lesson/add-video', name: 'api_lesson_add_video', methods: ['POST'])]
    public function addVideoToLesson(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['videoId']) || !isset($data['videoTitle']) || !isset($data['lessonId'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Missing required fields: videoId, videoTitle, lessonId',
            ], 400);
        }

        try {
            $lesson = $this->lessonRepository->find($data['lessonId']);

            if (!$lesson) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Lesson not found',
                ], 404);
            }

            // Update lesson with YouTube video URL
            $embedUrl = 'https://www.youtube-nocookie.com/embed/'.$data['videoId'];
            $lesson->setContent($embedUrl);
            $lesson->setTitle($data['videoTitle']);
            $lesson->setType('video');
            $lesson->setStatus('published');

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Video added to lesson successfully',
                'lesson' => [
                    'id' => $lesson->getId(),
                    'title' => $lesson->getTitle(),
                    'content' => $lesson->getContent(),
                    'type' => $lesson->getType(),
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to add video to lesson: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get lessons for dropdown selection with course hierarchy.
     */
    #[Route('/api/lessons', name: 'api_lessons_list', methods: ['GET'])]
    public function getLessons(): JsonResponse
    {
        try {
            // Get the currently logged-in user
            $user = $this->security->getUser();

            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Debug: Log user info
            error_log('User ID: '.$user->getId().', Email: '.$user->getEmail());

            // Get lessons for this instructor's courses only
            $lessons = $this->lessonRepository->createQueryBuilder('l')
                ->join('l.chapter', 'c')
                ->join('c.course', 'co')
                ->where('co.user = :user')
                ->andWhere('l.status = :status')
                ->setParameter('user', $user)
                ->setParameter('status', 'active')  // Changed from 'published' to 'active'
                ->orderBy('co.id', 'ASC')
                ->addOrderBy('c.id', 'ASC')
                ->addOrderBy('l.id', 'ASC')
                ->getQuery()
                ->getResult();

            // Debug: Log lesson count
            error_log('Found lessons: '.count($lessons));

            $coursesData = [];
            foreach ($lessons as $lesson) {
                $chapter = $lesson->getChapter();
                $course = $chapter ? $chapter->getCourse() : null;

                if ($course) {
                    $courseId = $course->getId();
                    $courseTitle = $course->getTitle();

                    // Initialize course if not exists
                    if (!isset($coursesData[$courseId])) {
                        $coursesData[$courseId] = [
                            'id' => $courseId,
                            'title' => $courseTitle,
                            'chapters' => [],
                        ];
                    }

                    // Add chapter if not exists
                    $chapterId = $chapter->getId();
                    $chapterTitle = $chapter->getTitle();

                    if (!isset($coursesData[$courseId]['chapters'][$chapterId])) {
                        $coursesData[$courseId]['chapters'][$chapterId] = [
                            'id' => $chapterId,
                            'title' => $chapterTitle,
                            'lessons' => [],
                        ];
                    }

                    // Add lesson to chapter
                    $coursesData[$courseId]['chapters'][$chapterId]['lessons'][] = [
                        'id' => $lesson->getId(),
                        'title' => $lesson->getTitle(),
                        'type' => $lesson->getType(),
                    ];
                }
            }

            // Convert to indexed array for JavaScript compatibility
            $coursesArray = array_values($coursesData);

            // Debug: Log final courses data
            error_log('Final courses data: '.print_r($coursesArray, true));

            return new JsonResponse([
                'success' => true,
                'courses' => $coursesArray,
            ]);
        } catch (\Exception $e) {
            error_log('Error in getLessons: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to load lessons: '.$e->getMessage(),
            ], 500);
        }
    }
}

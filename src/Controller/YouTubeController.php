<?php

namespace App\Controller;

use App\Service\YouTubeService;
use App\Entity\Lesson;
use App\Repository\LessonRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class YouTubeController extends AbstractController
{
    private YouTubeService $youTubeService;
    private Security $security;
    private LessonRepository $lessonRepository;
    private CourseRepository $courseRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        YouTubeService $youTubeService, 
        Security $security,
        LessonRepository $lessonRepository,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->youTubeService = $youTubeService;
        $this->security = $security;
        $this->lessonRepository = $lessonRepository;
        $this->courseRepository = $courseRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Video search interface page
     */
    #[Route('/instructor/video-search', name: 'instructor_video_search', methods: ['GET'])]
    public function videoSearchInterface(CourseRepository $courseRepository): Response
    {
        // Get the currently logged-in user
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get courses for this instructor
        $courses = $courseRepository->findBy(['user' => $user]);
        
        // Debug: Log course information
        error_log('Video Search - User ID: ' . $user->getId());
        error_log('Video Search - Courses found: ' . count($courses));
        
        foreach ($courses as $course) {
            error_log('Video Search - Course: ' . $course->getTitle() . ' (ID: ' . $course->getId() . ')');
            
            // Check chapters and lessons for this course
            $chapters = $course->getChapters();
            error_log('Video Search - Course ' . $course->getTitle() . ' has ' . $chapters->count() . ' chapters');
            
            foreach ($chapters as $chapter) {
                $lessons = $chapter->getLessons();
                error_log('Video Search - Chapter ' . $chapter->getTitle() . ' has ' . $lessons->count() . ' lessons');
                
                foreach ($lessons as $lesson) {
                    error_log('Video Search - Lesson: ' . $lesson->getTitle() . ' (Status: ' . $lesson->getStatus() . ')');
                }
            }
        }
        
        return $this->render('instructor/video-search.html.twig', [
            'courses' => $courses,
            'hasCourses' => count($courses) > 0
        ]);
    }

    /**
     * Search for YouTube videos
     */
    #[Route('/api/youtube/search', name: 'api_youtube_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $maxResults = $request->query->get('maxResults', 10);
        $maxResults = is_numeric($maxResults) ? (int)$maxResults : 10;

        if (empty($query)) {
            return new JsonResponse(['error' => 'Query parameter is required'], 400);
        }

        try {
            $videos = $this->youTubeService->searchEducationalVideos($query, $maxResults);
            
            return new JsonResponse([
                'success' => true,
                'videos' => $videos,
                'total' => count($videos)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to search videos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get video details
     */
    #[Route('/api/youtube/video/{videoId}', name: 'api_youtube_video_details', methods: ['GET'])]
    public function getVideoDetails(string $videoId): JsonResponse
    {
        try {
            $videoDetails = $this->youTubeService->getVideoDetails($videoId);
            
            return new JsonResponse([
                'success' => true,
                'video' => $videoDetails
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to get video details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get web development videos
     */
    #[Route('/api/youtube/web-dev-videos', name: 'api_youtube_web_dev', methods: ['GET'])]
    public function getWebDevelopmentVideos(): JsonResponse
    {
        try {
            $videos = $this->youTubeService->getWebDevelopmentVideos();
            
            return new JsonResponse([
                'success' => true,
                'videos' => $videos
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to get web development videos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug endpoint to check courses and lessons
     */
    #[Route('/api/debug/courses', name: 'api_debug_courses', methods: ['GET'])]
    public function debugCourses(CourseRepository $courseRepository): JsonResponse
    {
        try {
            // Get the currently logged-in user
            $user = $this->security->getUser();
            
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'User not authenticated'
                ], 401);
            }

            // Get user's courses
            $courses = $courseRepository->findBy(['user' => $user]);
            
            $debugData = [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'courses_count' => count($courses),
                'courses' => []
            ];
            
            foreach ($courses as $course) {
                $debugData['courses'][] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'status' => $course->getStatus(),
                    'chapters_count' => $course->getChapters()->count(),
                    'chapters' => []
                ];
                
                foreach ($course->getChapters() as $chapter) {
                    $debugData['courses'][count($debugData['courses']) - 1]['chapters'][] = [
                        'id' => $chapter->getId(),
                        'title' => $chapter->getTitle(),
                        'lessons_count' => $chapter->getLessons()->count(),
                        'lessons' => []
                    ];
                    
                    foreach ($chapter->getLessons() as $lesson) {
                        $debugData['courses'][count($debugData['courses']) - 1]['chapters'][count($debugData['courses'][count($debugData['courses']) - 1]['chapters']) - 1]['lessons'][] = [
                            'id' => $lesson->getId(),
                            'title' => $lesson->getTitle(),
                            'status' => $lesson->getStatus(),
                            'type' => $lesson->getType()
                        ];
                    }
                }
            }
            
            return new JsonResponse([
                'success' => true,
                'debug_data' => $debugData
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Debug failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug endpoint to check lessons API
     */
    #[Route('/api/debug/lessons', name: 'api_debug_lessons', methods: ['GET'])]
    public function debugLessons(): JsonResponse
    {
        try {
            // Get the currently logged-in user
            $user = $this->security->getUser();
            
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'User not authenticated'
                ], 401);
            }

            error_log('Debug Lessons API - User ID: ' . $user->getId());
            
            // Test the lessons API directly
            $lessonsController = new \App\Controller\LessonVideoController(
                $this->lessonRepository,
                $this->courseRepository,
                $this->entityManager,
                $this->security,
                $this->youTubeService
            );
            
            $response = $lessonsController->getLessons();
            
            return $response;
            
        } catch (\Exception $e) {
            error_log('Debug Lessons API Error: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'error' => 'Debug failed: ' . $e->getMessage()
            ], 500);
        }
    }
}

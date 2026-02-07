<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LessonVideoController extends AbstractController
{
    private LessonRepository $lessonRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LessonRepository $lessonRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->lessonRepository = $lessonRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Add YouTube video to lesson
     */
    #[Route('/api/lesson/add-video', name: 'api_lesson_add_video', methods: ['POST'])]
    public function addVideoToLesson(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['videoId']) || !isset($data['videoTitle']) || !isset($data['lessonId'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Missing required fields: videoId, videoTitle, lessonId'
            ], 400);
        }

        try {
            $lesson = $this->lessonRepository->find($data['lessonId']);
            
            if (!$lesson) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Lesson not found'
                ], 404);
            }

            // Update lesson with YouTube video URL
            $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $data['videoId'];
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
                    'type' => $lesson->getType()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to add video to lesson: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lessons for dropdown selection with course hierarchy
     */
    #[Route('/api/lessons', name: 'api_lessons_list', methods: ['GET'])]
    public function getLessons(): JsonResponse
    {
        try {
            // Get lessons with their chapters and courses
            $lessons = $this->lessonRepository->findBy(['status' => 'published'], ['id' => 'DESC']);
            
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
                            'chapters' => []
                        ];
                    }
                    
                    // Add chapter if not exists
                    $chapterId = $chapter->getId();
                    $chapterTitle = $chapter->getTitle();
                    
                    if (!isset($coursesData[$courseId]['chapters'][$chapterId])) {
                        $coursesData[$courseId]['chapters'][$chapterId] = [
                            'id' => $chapterId,
                            'title' => $chapterTitle,
                            'lessons' => []
                        ];
                    }
                    
                    // Add lesson to chapter
                    $coursesData[$courseId]['chapters'][$chapterId]['lessons'][] = [
                        'id' => $lesson->getId(),
                        'title' => $lesson->getTitle(),
                        'type' => $lesson->getType()
                    ];
                }
            }
            
            // Convert to indexed array for JSON response
            $coursesArray = array_values($coursesData);
            
            return new JsonResponse([
                'success' => true,
                'courses' => $coursesArray
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch lessons: ' . $e->getMessage()
            ], 500);
        }
    }
}

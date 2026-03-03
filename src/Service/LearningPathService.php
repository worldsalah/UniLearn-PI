<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\CourseTestResult;
use App\Entity\Chapter;
use App\Entity\Lesson;
use App\Repository\ChapterRepository;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;

class LearningPathService
{
    private EntityManagerInterface $entityManager;
    private ChapterRepository $chapterRepository;
    private LessonRepository $lessonRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ChapterRepository $chapterRepository,
        LessonRepository $lessonRepository
    ) {
        $this->entityManager = $entityManager;
        $this->chapterRepository = $chapterRepository;
        $this->lessonRepository = $lessonRepository;
    }

    /**
     * Determine the appropriate starting lesson based on test performance
     */
    public function getRecommendedStartingPoint(Course $course, ?CourseTestResult $testResult = null): array
    {
        $chapters = $this->chapterRepository->findBy(['course' => $course], ['sortOrder' => 'ASC']);
        
        if ($testResult === null) {
            // First time taking test - start from beginning
            return [
                'chapter' => $chapters[0] ?? null,
                'lesson' => $this->getFirstLesson($chapters[0] ?? null),
                'recommendation' => 'Start from the beginning',
                'reason' => 'This is your first time taking this course'
            ];
        }

        $score = $testResult->getPercentage();
        
        if ($score >= 90) {
            // Excellent performance - can continue or jump to advanced topics
            return [
                'chapter' => $this->getCurrentChapter($chapters, $testResult),
                'lesson' => $this->getNextRecommendedLesson($chapters, $testResult),
                'recommendation' => 'Continue with advanced topics',
                'reason' => 'Excellent performance! You can continue with more advanced concepts.'
            ];
        } elseif ($score >= 70) {
            // Good performance - continue from current point
            return [
                'chapter' => $this->getCurrentChapter($chapters, $testResult),
                'lesson' => $this->getNextRecommendedLesson($chapters, $testResult),
                'recommendation' => 'Continue learning',
                'reason' => 'Good job! Continue with the next lessons to build on your knowledge.'
            ];
        } elseif ($score >= 50) {
            // Average performance - review current chapter
            return [
                'chapter' => $this->getReviewChapter($chapters, $testResult),
                'lesson' => $this->getFirstLessonOfChapter($this->getReviewChapter($chapters, $testResult)),
                'recommendation' => 'Review and retry',
                'reason' => 'You need to review the current chapter before moving forward. Take time to understand the concepts better.'
            ];
        } else {
            // Poor performance - go back to basics
            return [
                'chapter' => $this->getBasicsChapter($chapters),
                'lesson' => $this->getFirstLessonOfChapter($this->getBasicsChapter($chapters)),
                'recommendation' => 'Back to basics',
                'reason' => 'Let\'s go back to strengthen your foundation. Review the fundamental concepts.'
            ];
        }
    }

    /**
     * Get the current or most appropriate chapter based on test answers
     */
    private function getCurrentChapter(array $chapters, CourseTestResult $testResult): ?Chapter
    {
        // For now, return the first chapter (can be enhanced later with actual test data)
        return $chapters[0] ?? null;
    }

    /**
     * Get the next recommended lesson based on performance
     */
    private function getNextRecommendedLesson(array $chapters, CourseTestResult $testResult): ?Lesson
    {
        $currentChapter = $this->getCurrentChapter($chapters, $testResult);
        
        if ($currentChapter !== null) {
            $lessons = $currentChapter->getLessons()->toArray();
            return $lessons[0] ?? null; // Return first lesson of current chapter
        }
        
        return $this->getFirstLesson($chapters[0] ?? null);
    }

    /**
     * Get a chapter that needs review based on performance
     */
    private function getReviewChapter(array $chapters, CourseTestResult $testResult): ?Chapter
    {
        // Return the first chapter for review (can be made more sophisticated)
        return $chapters[0] ?? null;
    }

    /**
     * Get a basics chapter for students who need to review fundamentals
     */
    private function getBasicsChapter(array $chapters): ?Chapter
    {
        // Return the first chapter (can be enhanced to find actual basics chapter)
        return $chapters[0] ?? null;
    }

    /**
     * Get the first lesson of a chapter
     */
    private function getFirstLessonOfChapter(?Chapter $chapter): ?Lesson
    {
        if ($chapter !== null) {
            $lessons = $chapter->getLessons()->toArray();
            return $lessons[0] ?? null;
        }
        
        return null;
    }

    /**
     * Get the first lesson of the first chapter
     */
    private function getFirstLesson(?Chapter $chapter): ?Lesson
    {
        if ($chapter !== null) {
            $lessons = $chapter->getLessons()->toArray();
            foreach ($lessons as $lesson) {
                return $lesson; // Return first available lesson
            }
        }
        
        return null;
    }

    /**
     * Get chapter by difficulty level
     */
    private function getChapterByDifficulty(array $chapters, string $level): ?Chapter
    {
        foreach ($chapters as $chapter) {
            if (stripos(strtolower($chapter->getTitle() ?? ''), strtolower($level)) !== false) {
                return $chapter;
            }
        }
        
        return $chapters[0] ?? null;
    }

    /**
     * Calculate learning progress and recommendations
     */
    public function getLearningProgress(Course $course, ?CourseTestResult $testResult): array
    {
        $chapters = $this->chapterRepository->findBy(['course' => $course], ['sortOrder' => 'ASC']);
        $totalChapters = count($chapters);
        
        if ($testResult === null) {
            return [
                'completedChapters' => 0,
                'totalChapters' => $totalChapters,
                'progressPercentage' => 0,
                'nextChapter' => $chapters[0] ?? null,
                'status' => 'not_started'
            ];
        }

        $score = $testResult->getPercentage();
        $completedChapters = $this->estimateCompletedChapters($score, $totalChapters);
        
        return [
            'completedChapters' => $completedChapters,
            'totalChapters' => $totalChapters,
            'progressPercentage' => round(($completedChapters / $totalChapters) * 100, 2),
            'nextChapter' => $this->getNextChapter($chapters, $completedChapters),
            'status' => $this->getLearningStatus($score, $totalChapters)
        ];
    }

    /**
     * Estimate completed chapters based on test score
     */
    private function estimateCompletedChapters(float $score, int $totalChapters): int
    {
        // Simple estimation: 80% score = 50% chapters completed
        $estimatedProgress = ($score / 100) * $totalChapters;
        return (int) min($estimatedProgress, $totalChapters);
    }

    /**
     * Get the next chapter to study
     */
    private function getNextChapter(array $chapters, int $completedChapters): ?Chapter
    {
        $nextIndex = min($completedChapters, count($chapters) - 1);
        return $chapters[$nextIndex] ?? null;
    }

    /**
     * Get learning status based on performance
     */
    private function getLearningStatus(float $score, int $totalChapters): string
    {
        $progressPercentage = ($score / 100) * $totalChapters;
        
        if ($score >= 90) {
            return 'excellent';
        } elseif ($score >= 70) {
            return 'good';
        } elseif ($score >= 50) {
            return 'average';
        } else {
            return 'needs_improvement';
        }
    }
}

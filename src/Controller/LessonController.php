<?php

namespace App\Controller;

use App\Entity\Chapter;
use App\Entity\Lesson;
use App\Repository\ChapterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LessonController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/lesson/{id}', name: 'app_lesson_show', requirements: ['id' => '\d+'])]
    public function show(Lesson $lesson, ChapterRepository $chapterRepository): Response
    {
        $chapter = $lesson->getChapter();
        $course = $chapter ? $chapter->getCourse() : null;
        
        if (!$course) {
            throw $this->createNotFoundException('Course not found for this lesson');
        }

        // Get all chapters with their lessons for the sidebar
        $chapters = $this->entityManager->createQueryBuilder()
            ->select('c', 'l')
            ->from(Chapter::class, 'c')
            ->leftJoin('c.lessons', 'l')
            ->where('c.course = :course')
            ->setParameter('course', $course)
            ->orderBy('c.id', 'ASC')
            ->addOrderBy('l.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();

        // Get previous and next lessons
        $previousLesson = $this->getAdjacentLesson($lesson, $course, 'previous');
        $nextLesson = $this->getAdjacentLesson($lesson, $course, 'next');

        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'chapter' => $chapter,
            'course' => $course,
            'chapters' => $chapters,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
        ]);
    }

    private function getAdjacentLesson(Lesson $currentLesson, $course, string $direction = 'next'): ?Lesson
    {
        $currentChapter = $currentLesson->getChapter();
        $qb = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Lesson::class, 'l')
            ->join('l.chapter', 'c')
            ->where('c.course = :course')
            ->setParameter('course', $course);

        if ($direction === 'next') {
            $qb->andWhere('(c.id > :chapterId) OR (c.id = :chapterId AND l.sortOrder > :lessonOrder)')
               ->orderBy('c.id', 'ASC')
               ->addOrderBy('l.sortOrder', 'ASC');
        } else {
            $qb->andWhere('(c.id < :chapterId) OR (c.id = :chapterId AND l.sortOrder < :lessonOrder)')
               ->orderBy('c.id', 'DESC')
               ->addOrderBy('l.sortOrder', 'DESC');
        }

        $qb->setParameter('chapterId', $currentChapter->getId())
           ->setParameter('lessonOrder', $currentLesson->getSortOrder())
           ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}

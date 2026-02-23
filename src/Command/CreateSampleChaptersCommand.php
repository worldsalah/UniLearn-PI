<?php

namespace App\Command;

use App\Entity\Chapter;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-sample-chapters',
    description: 'Create sample chapters and lessons for existing courses'
)]
class CreateSampleChaptersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private CourseRepository $courseRepository;

    public function __construct(EntityManagerInterface $entityManager, CourseRepository $courseRepository)
    {
        $this->entityManager = $entityManager;
        $this->courseRepository = $courseRepository;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $courses = $this->courseRepository->findAll();

        if (empty($courses)) {
            $output->writeln('<error>No courses found. Please create courses first.</error>');

            return Command::FAILURE;
        }

        $sampleChapters = [
            'Introduction to Web Development',
            'HTML Fundamentals',
            'CSS Basics',
            'JavaScript Essentials',
            'Advanced Topics',
        ];

        $sampleLessons = [
            'Getting Started',
            'Basic Concepts',
            'Practical Examples',
            'Hands-on Exercise',
            'Summary and Quiz',
        ];

        $sampleVideoUrls = [
            'https://www.youtube.com/embed/kUMe1FH4CHE', // HTML & CSS for Beginners - Full Course
            'https://www.youtube.com/embed/UB1O30fR-EE', // JavaScript Full Course for Beginners
            'https://www.youtube.com/embed/1Rs2ND1ryYc', // CSS Full Course for Beginners
            'https://www.youtube.com/embed/W6NZfCO5SIk', // JavaScript Tutorial for Beginners
            'https://www.youtube.com/embed/7S_tz1z_5aA',  // Web Development Full Course
        ];

        foreach ($courses as $course) {
            $output->writeln("Creating chapters for course: {$course->getTitle()}");

            // Create 2-4 chapters per course
            $numChapters = rand(2, 4);
            for ($i = 0; $i < $numChapters; ++$i) {
                $chapter = new Chapter();
                $chapter->setTitle($sampleChapters[$i].' - Chapter '.($i + 1));
                $chapter->setCourse($course);

                // Create 2-5 lessons per chapter
                $numLessons = rand(2, 5);
                for ($j = 0; $j < $numLessons; ++$j) {
                    $lesson = new Lesson();
                    $lesson->setTitle($sampleLessons[$j].' - Lesson '.($j + 1));
                    $lesson->setDuration(rand(15, 60).' min');
                    $lesson->setType('video');
                    $lesson->setContent($sampleVideoUrls[$j]);
                    $lesson->setIsPreview(0 === $j); // First lesson is preview
                    $lesson->setStatus('published');
                    $lesson->setSortOrder($j + 1);
                    $lesson->setChapter($chapter);

                    $chapter->addLesson($lesson);
                }

                $course->addChapter($chapter);
                $this->entityManager->persist($chapter);

                $output->writeln("  - Added chapter: {$chapter->getTitle()} with {$numLessons} lessons");
            }
        }

        $this->entityManager->flush();

        $output->writeln('<info>Successfully created sample chapters and lessons for all courses.</info>');

        return Command::SUCCESS;
    }
}

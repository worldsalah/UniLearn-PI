<?php

namespace App\Command;

use App\Entity\Course;
use App\Entity\CourseTest;
use App\Entity\CourseTestQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-course-tests',
    description: 'Generate tests for all courses with 20 questions each (10 easy, 5 medium, 5 hard)'
)]
class GenerateCourseTestsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate tests for all courses with 20 questions each (10 easy, 5 medium, 5 hard)')
            ->addArgument('course-id', null, 'Optional: Generate test for specific course ID only');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $courseId = $input->getArgument('course-id');
        
        if ($courseId !== null) {
            // Generate test for specific course
            $course = $this->entityManager->getRepository(Course::class)->find($courseId);
            if ($course === null) {
                $output->writeln('<error>Course with ID ' . $courseId . ' not found</error>');
                return Command::FAILURE;
            }
            
            $this->generateTestForCourse($course, $output);
        } else {
            // Generate tests for all courses
            $courses = $this->entityManager->getRepository(Course::class)->findAll();
            
            foreach ($courses as $course) {
                $this->generateTestForCourse($course, $output);
            }
        }
        
        $output->writeln('<info>Test generation completed!</info>');
        return Command::SUCCESS;
    }

    private function generateTestForCourse(Course $course, OutputInterface $output): void
    {
        $output->writeln('<info>Generating test for course: ' . $course->getTitle() . '</info>');
        
        // Check if test already exists
        $existingTest = $this->entityManager->getRepository(CourseTest::class)
            ->findOneBy(['course' => $course]);
        
        if ($existingTest !== null) {
            // Remove existing questions
            foreach ($existingTest->getQuestions() as $question) {
                $this->entityManager->remove($question);
            }
            $this->entityManager->flush();
        } else {
            // Create new test
            $test = new CourseTest();
            $test->setCourse($course);
            $test->setTitle($course->getTitle() . ' - Final Assessment');
            $test->setDescription('Comprehensive test covering all course material with varying difficulty levels');
            $test->setTimeLimit(20); // 20 minutes
            $test->setPassingScore(70); // 70% to pass
            $this->entityManager->persist($test);
        }
        
        // Generate 20 questions
        $questions = $this->generateQuestionsForCourse($course);
        
        foreach ($questions as $questionData) {
            $question = new CourseTestQuestion();
            $question->setCourseTest($existingTest ?? $test);
            $question->setQuestion($questionData['question']);
            $question->setOptions($questionData['options']);
            $question->setCorrectAnswer($questionData['correct']);
            $question->setDifficulty($questionData['difficulty']);
            $question->setExplanation($questionData['explanation']);
            $this->entityManager->persist($question);
        }
        
        $this->entityManager->flush();
        $output->writeln('<info>Generated ' . count($questions) . ' questions for ' . $course->getTitle() . '</info>');
    }

    private function generateQuestionsForCourse(Course $course): array
    {
        $courseTitle = strtolower($course->getTitle() ?? '');
        $questions = [];
        
        // Generate 10 easy questions
        for ($i = 1; $i <= 10; $i++) {
            $questions[] = $this->generateEasyQuestion($course, $i);
        }
        
        // Generate 5 medium questions
        for ($i = 11; $i <= 15; $i++) {
            $questions[] = $this->generateMediumQuestion($course, $i);
        }
        
        // Generate 5 hard questions
        for ($i = 16; $i <= 20; $i++) {
            $questions[] = $this->generateHardQuestion($course, $i);
        }
        
        return $questions;
    }

    private function generateEasyQuestion(Course $course, int $questionNumber): array
    {
        $courseTitle = $course->getTitle();
        $templates = [
            [
                'question' => "What is the main purpose of {$courseTitle}?",
                'options' => [
                    "To learn fundamental concepts",
                    "To waste time",
                    "To confuse students",
                    "To make money only"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "The main purpose of any course is to help students learn fundamental concepts in a structured way."
            ],
            [
                'question' => "Which of the following is a key benefit of {$courseTitle}?",
                'options' => [
                    "Gaining new knowledge",
                    "Losing sleep",
                    "Wasting money",
                    "Getting confused"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "The primary benefit of taking a course is acquiring new knowledge and skills."
            ],
            [
                'question' => "How should you approach learning {$courseTitle}?",
                'options' => [
                    "With dedication and practice",
                    "By skipping lessons",
                    "Without any effort",
                    "By cheating"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "Learning requires dedication, practice, and consistent effort to be effective."
            ],
            [
                'question' => "What is the first step in learning {$courseTitle}?",
                'options' => [
                    "Starting with basics",
                    "Jumping to advanced topics",
                    "Giving up immediately",
                    "Skipping the course"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "Always start with the basics to build a strong foundation."
            ],
            [
                'question' => "Why is practice important in {$courseTitle}?",
                'options' => [
                    "It reinforces learning",
                    "It wastes time",
                    "It's unnecessary",
                    "It's too difficult"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "Practice reinforces what you've learned and helps with retention."
            ],
            [
                'question' => "What should you do if you don't understand something in {$courseTitle}?",
                'options' => [
                    "Ask for help or review",
                    "Give up immediately",
                    "Ignore it",
                    "Blame the instructor"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "Seeking help or reviewing material is the best approach when you don't understand something."
            ],
            [
                'question' => "How often should you study {$courseTitle}?",
                'options' => [
                    "Regularly and consistently",
                    "Only on weekends",
                    "Never",
                    "Once a year"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "Regular, consistent study is key to effective learning."
            ],
            [
                'question' => "What is the best way to remember what you learn in {$courseTitle}?",
                'options' => [
                    "Review and apply concepts",
                    "Forget everything",
                    "Cram at the last minute",
                    "Never review"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "Reviewing and applying concepts helps with long-term retention."
            ],
            [
                'question' => "Why is setting goals important for {$courseTitle}?",
                'options' => [
                    "It provides direction",
                    "It's unnecessary",
                    "It confuses learners",
                    "It wastes time"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "Goals provide direction and motivation throughout your learning journey."
            ],
            [
                'question' => "What should you do after completing {$courseTitle}?",
                'options' => [
                    "Apply what you learned",
                    "Forget everything",
                    "Take another course immediately",
                    "Never use the knowledge"
                ],
                'correct' => 0,
                'difficulty' => 'easy',
                'explanation' => "Applying what you've learned is crucial for skill development."
            ]
        ];
        
        return $templates[$questionNumber - 1] ?? $templates[0];
    }

    private function generateMediumQuestion(Course $course, int $questionNumber): array
    {
        $courseTitle = $course->getTitle();
        $templates = [
            [
                'question' => "How would you apply the concepts from {$courseTitle} in a real-world scenario?",
                'options' => [
                    "By identifying relevant situations",
                    "By ignoring the concepts",
                    "By making things up",
                    "By avoiding real-world applications"
                ],
                'correct' => 0,
                'difficulty' => 'medium',
                'explanation' => "The key is to identify real-world situations where the concepts can be practically applied."
            ],
            [
                'question' => "What is the relationship between theory and practice in {$courseTitle}?",
                'options' => [
                    "Theory guides practice",
                    "Practice replaces theory",
                    "They are unrelated",
                    "Theory is useless"
                ],
                'correct' => 0,
                'difficulty' => 'medium',
                'explanation' => "Theory provides the foundation that guides practical application."
            ],
            [
                'question' => "How would you troubleshoot a problem using {$courseTitle} knowledge?",
                'options' => [
                    "Systematic problem-solving",
                    "Random guessing",
                    "Giving up immediately",
                    "Blaming others"
                ],
                'correct' => 0,
                'difficulty' => 'medium',
                'explanation' => "Systematic problem-solving using course knowledge is the most effective approach."
            ],
            [
                'question' => "What is the importance of context when applying {$courseTitle} concepts?",
                'options' => [
                    "Context determines application",
                    "Context is irrelevant",
                    "One size fits all",
                    "Context complicates things"
                ],
                'correct' => 0,
                'difficulty' => 'medium',
                'explanation' => "Context is crucial as it determines how and when concepts should be applied."
            ],
            [
                'question' => "How would you evaluate your progress in {$courseTitle}?",
                'options' => [
                    "Through assessments and feedback",
                    "By guessing",
                    "By comparing with others",
                    "By ignoring progress"
                ],
                'correct' => 0,
                'difficulty' => 'medium',
                'explanation' => "Regular assessments and feedback provide objective measures of progress."
            ]
        ];
        
        return $templates[$questionNumber - 11] ?? $templates[0];
    }

    private function generateHardQuestion(Course $course, int $questionNumber): array
    {
        $courseTitle = $course->getTitle();
        $templates = [
            [
                'question' => "How would you innovate or extend the concepts learned in {$courseTitle}?",
                'options' => [
                    "By building on foundations",
                    "By copying others exactly",
                    "By staying within boundaries",
                    "By avoiding innovation"
                ],
                'correct' => 0,
                'difficulty' => 'hard',
                'explanation' => "True innovation involves building on foundational concepts while thinking creatively."
            ],
            [
                'question' => "What is the ethical consideration when applying {$courseTitle} knowledge?",
                'options' => [
                    "Consider impact on others",
                    "Ignore ethics completely",
                    "Focus only on personal gain",
                    "Ethics don't matter"
                ],
                'correct' => 0,
                'difficulty' => 'hard',
                'explanation' => "Ethical considerations require thinking about the impact of your actions on others."
            ],
            [
                'question' => "How would you integrate {$courseTitle} with other disciplines?",
                'options' => [
                    "Through interdisciplinary connections",
                    "By keeping disciplines separate",
                    "By avoiding integration",
                    "By forcing connections"
                ],
                'correct' => 0,
                'difficulty' => 'hard',
                'explanation' => "Natural interdisciplinary connections enhance understanding and innovation."
            ],
            [
                'question' => "What is the long-term vision for applying {$courseTitle} knowledge?",
                'options' => [
                    "Continuous improvement and adaptation",
                    "Short-term gains only",
                    "Static application",
                    "No future vision"
                ],
                'correct' => 0,
                'difficulty' => 'hard',
                'explanation' => "Long-term vision involves continuous improvement and adaptation to new challenges."
            ],
            [
                'question' => "How would you contribute to the {$courseTitle} community?",
                'options' => [
                    "By sharing knowledge and helping others",
                    "By keeping knowledge to yourself",
                    "By criticizing others",
                    "By avoiding the community"
                ],
                'correct' => 0,
                'difficulty' => 'hard',
                'explanation' => "Contributing to the community through knowledge sharing creates a collaborative learning environment."
            ]
        ];
        
        return $templates[$questionNumber - 16] ?? $templates[0];
    }
}

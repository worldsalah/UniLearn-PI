<?php

namespace App\Command;

use App\Entity\Course;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-sample-courses',
    description: 'Create sample courses for testing'
)]
class CreateSampleCoursesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $teachers = $this->userRepository->findBy(['role' => 'teacher']);

        if (empty($teachers)) {
            $output->writeln('<error>No teachers found. Please run app:insert-users first.</error>');

            return Command::FAILURE;
        }

        $sampleCourses = [
            [
                'title' => 'JavaScript Fundamentals',
                'shortDescription' => 'Learn the basics of JavaScript programming from scratch.',
                'category' => 'Programming',
                'level' => 'Beginner',
                'price' => 49.99,
                'duration' => 25.5,
                'language' => 'English',
                'requirements' => 'Basic computer skills',
                'learningOutcomes' => 'Understand JavaScript syntax and concepts',
                'targetAudience' => 'Beginners in programming',
                'status' => 'active',
            ],
            [
                'title' => 'Advanced React Development',
                'shortDescription' => 'Master advanced React concepts and build complex applications.',
                'category' => 'Programming',
                'level' => 'Advanced',
                'price' => 89.99,
                'duration' => 40.0,
                'language' => 'English',
                'requirements' => 'JavaScript and React basics',
                'learningOutcomes' => 'Build scalable React applications',
                'targetAudience' => 'Intermediate React developers',
                'status' => 'active',
            ],
            [
                'title' => 'Python for Beginners',
                'shortDescription' => 'Start your Python programming journey with this comprehensive course.',
                'category' => 'Programming',
                'level' => 'Beginner',
                'price' => 39.99,
                'duration' => 30.0,
                'language' => 'English',
                'requirements' => 'Basic computer skills',
                'learningOutcomes' => 'Write Python programs confidently',
                'targetAudience' => 'Programming beginners',
                'status' => 'inactive',
            ],
            [
                'title' => 'Database Design with SQL',
                'shortDescription' => 'Learn database design principles and SQL programming.',
                'category' => 'Database',
                'level' => 'Intermediate',
                'price' => 59.99,
                'duration' => 35.0,
                'language' => 'English',
                'requirements' => 'Basic programming knowledge',
                'learningOutcomes' => 'Design and query databases effectively',
                'targetAudience' => 'Developers who want to learn databases',
                'status' => 'active',
            ],
            [
                'title' => 'Web Design with HTML & CSS',
                'shortDescription' => 'Create beautiful and responsive websites with HTML and CSS.',
                'category' => 'Design',
                'level' => 'Beginner',
                'price' => 34.99,
                'duration' => 20.0,
                'language' => 'English',
                'requirements' => 'No prior experience needed',
                'learningOutcomes' => 'Build modern, responsive websites',
                'targetAudience' => 'Aspiring web designers',
                'status' => 'active',
            ],
            [
                'title' => 'Node.js Backend Development',
                'shortDescription' => 'Build powerful backend applications with Node.js and Express.',
                'category' => 'Programming',
                'level' => 'Intermediate',
                'price' => 69.99,
                'duration' => 45.0,
                'language' => 'English',
                'requirements' => 'JavaScript knowledge required',
                'learningOutcomes' => 'Create RESTful APIs and backend services',
                'targetAudience' => 'JavaScript developers',
                'status' => 'active',
            ],
            [
                'title' => 'Machine Learning Basics',
                'shortDescription' => 'Introduction to machine learning concepts and algorithms.',
                'category' => 'Data Science',
                'level' => 'Intermediate',
                'price' => 99.99,
                'duration' => 50.0,
                'language' => 'English',
                'requirements' => 'Python programming and basic math',
                'learningOutcomes' => 'Understand and implement ML algorithms',
                'targetAudience' => 'Data science enthusiasts',
                'status' => 'pending',
            ],
            [
                'title' => 'Digital Marketing Mastery',
                'shortDescription' => 'Learn digital marketing strategies to grow your business online.',
                'category' => 'Marketing',
                'level' => 'Beginner',
                'price' => 44.99,
                'duration' => 28.0,
                'language' => 'English',
                'requirements' => 'Basic computer skills',
                'learningOutcomes' => 'Create effective digital marketing campaigns',
                'targetAudience' => 'Business owners and marketers',
                'status' => 'active',
            ],
            [
                'title' => 'Mobile App Development with Flutter',
                'shortDescription' => 'Build cross-platform mobile applications with Flutter.',
                'category' => 'Mobile Development',
                'level' => 'Intermediate',
                'price' => 79.99,
                'duration' => 42.0,
                'language' => 'English',
                'requirements' => 'Programming basics required',
                'learningOutcomes' => 'Create iOS and Android apps with one codebase',
                'targetAudience' => 'Mobile app developers',
                'status' => 'active',
            ],
            [
                'title' => 'Cybersecurity Fundamentals',
                'shortDescription' => 'Learn essential cybersecurity concepts and best practices.',
                'category' => 'Security',
                'level' => 'Beginner',
                'price' => 54.99,
                'duration' => 32.0,
                'language' => 'English',
                'requirements' => 'Basic IT knowledge',
                'learningOutcomes' => 'Understand security threats and protection measures',
                'targetAudience' => 'IT professionals and students',
                'status' => 'inactive',
            ],
            [
                'title' => 'Vue.js Complete Guide',
                'shortDescription' => 'Master Vue.js and build modern single-page applications.',
                'category' => 'Programming',
                'level' => 'Intermediate',
                'price' => 64.99,
                'duration' => 38.0,
                'language' => 'English',
                'requirements' => 'HTML, CSS, and JavaScript basics',
                'learningOutcomes' => 'Build dynamic web applications with Vue.js',
                'targetAudience' => 'Web developers',
                'status' => 'active',
            ],
            [
                'title' => 'Data Science with Python',
                'shortDescription' => 'Analyze data and create insights using Python data science libraries.',
                'category' => 'Data Science',
                'level' => 'Intermediate',
                'price' => 84.99,
                'duration' => 48.0,
                'language' => 'English',
                'requirements' => 'Python programming knowledge',
                'learningOutcomes' => 'Perform data analysis and visualization',
                'targetAudience' => 'Data analysts and scientists',
                'status' => 'pending',
            ],
            [
                'title' => 'UI/UX Design Principles',
                'shortDescription' => 'Learn user interface and user experience design fundamentals.',
                'category' => 'Design',
                'level' => 'Beginner',
                'price' => 39.99,
                'duration' => 25.0,
                'language' => 'English',
                'requirements' => 'No design experience needed',
                'learningOutcomes' => 'Create user-friendly interfaces and experiences',
                'targetAudience' => 'Design beginners and developers',
                'status' => 'active',
            ],
            [
                'title' => 'DevOps with Docker and Kubernetes',
                'shortDescription' => 'Master containerization and orchestration for modern applications.',
                'category' => 'DevOps',
                'level' => 'Advanced',
                'price' => 94.99,
                'duration' => 55.0,
                'language' => 'English',
                'requirements' => 'Linux and command-line experience',
                'learningOutcomes' => 'Deploy and manage containerized applications',
                'targetAudience' => 'System administrators and DevOps engineers',
                'status' => 'active',
            ],
            [
                'title' => 'Photography for Beginners',
                'shortDescription' => 'Learn the art of photography from composition to post-processing.',
                'category' => 'Creative',
                'level' => 'Beginner',
                'price' => 29.99,
                'duration' => 18.0,
                'language' => 'English',
                'requirements' => 'Any camera (even smartphone)',
                'learningOutcomes' => 'Take stunning photographs and edit them',
                'targetAudience' => 'Photography enthusiasts',
                'status' => 'inactive',
            ],
        ];

        foreach ($sampleCourses as $index => $courseData) {
            $course = new Course();
            $course->setTitle($courseData['title']);
            $course->setShortDescription($courseData['shortDescription']);
            $course->setCategory($courseData['category']);
            $course->setLevel($courseData['level']);
            $course->setPrice($courseData['price']);
            $course->setDuration($courseData['duration']);
            $course->setLanguage($courseData['language']);
            $course->setRequirements($courseData['requirements']);
            $course->setLearningOutcomes($courseData['learningOutcomes']);
            $course->setTargetAudience($courseData['targetAudience']);
            $course->setStatus($courseData['status']);

            // Assign to a teacher (round-robin)
            $teacher = $teachers[$index % count($teachers)];
            $course->setUser($teacher);

            $this->entityManager->persist($course);

            $output->writeln("Created course '{$courseData['title']}' for teacher '{$teacher->getName()}'");
        }

        $this->entityManager->flush();

        $output->writeln('<info>Successfully created '.count($sampleCourses).' sample courses.</info>');

        return Command::SUCCESS;
    }
}

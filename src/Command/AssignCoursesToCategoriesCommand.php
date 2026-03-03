<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AssignCoursesToCategoriesCommand extends Command
{
    protected static $defaultName = 'app:assign-courses-categories';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this->setDescription('Assign each course to its proper category based on title');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->em->getConnection();
        
        // Category ID mapping
        $categories = [
            'Web Development' => 1,
            'Design' => 2,
            'Marketing' => 3,
            'Writing' => 4,
            'Other' => 5,
            'Mathematics' => 7,
            'Physics' => 8,
            'Chemistry' => 9,
            'Biology' => 10,
            'Computer Science' => 11,
            'Mobile Development' => 12,
            'Python Programming' => 13,
            'Java Programming' => 14,
            'Data Science' => 15,
            'Cybersecurity' => 16,
            'Cloud Computing' => 17,
            'Database Management' => 18,
            'English' => 19,
            'French' => 20,
            'Spanish' => 21,
            'German' => 22,
            'Arabic' => 23,
            'Chinese' => 24,
            'Japanese' => 25,
            'Business Management' => 26,
            'Finance' => 27,
            'Economics' => 28,
            'Entrepreneurship' => 29,
            'Graphic Design' => 30,
            'Music' => 31,
            'Photography' => 32,
            'Video Production' => 33,
            'Drawing & Painting' => 34,
            'SAT Preparation' => 35,
            'GRE Preparation' => 36,
            'GMAT Preparation' => 37,
            'MCAT Preparation' => 38,
            'TOEFL Preparation' => 39,
            'IELTS Preparation' => 40,
            'Public Speaking' => 41,
            'Time Management' => 42,
            'Career Coaching' => 43,
            'Yoga' => 44,
            'Fitness Training' => 45,
            'Mental Health' => 46,
            'History' => 47,
            'Geography' => 48,
            'Philosophy' => 49,
            'Psychology' => 50,
            'Sociology' => 51,
            'Electrical Engineering' => 52,
            'Mechanical Engineering' => 53,
            'Civil Engineering' => 54,
        ];
        
        // Course to category mapping based on title keywords
        $courseCategoryMap = [
            // Web Development
            'React' => 'Web Development',
            'Angular' => 'Web Development',
            'Vue' => 'Web Development',
            'JavaScript' => 'Web Development',
            'TypeScript' => 'Web Development',
            'Node' => 'Web Development',
            'HTML' => 'Web Development',
            'CSS' => 'Web Development',
            'Frontend' => 'Web Development',
            'Backend' => 'Web Development',
            'Full Stack' => 'Web Development',
            'Web Development' => 'Web Development',
            'Web Design' => 'Web Development',
            
            // Python Programming
            'Python' => 'Python Programming',
            'Django' => 'Python Programming',
            'Flask' => 'Python Programming',
            
            // Java Programming
            'Java ' => 'Java Programming',
            'Spring' => 'Java Programming',
            
            // Data Science & AI
            'Machine Learning' => 'Data Science',
            'Data Science' => 'Data Science',
            'Deep Learning' => 'Data Science',
            'Neural Networks' => 'Data Science',
            'Natural Language Processing' => 'Data Science',
            'Computer Vision' => 'Data Science',
            'TensorFlow' => 'Data Science',
            'Keras' => 'Data Science',
            'Statistics' => 'Data Science',
            'AI Ethics' => 'Data Science',
            'AI ' => 'Data Science',
            'Data Analysis' => 'Data Science',
            
            // Mobile Development
            'React Native' => 'Mobile Development',
            'Flutter' => 'Mobile Development',
            'Swift' => 'Mobile Development',
            'iOS' => 'Mobile Development',
            'Android' => 'Mobile Development',
            'Kotlin' => 'Mobile Development',
            
            // Cybersecurity
            'Cybersecurity' => 'Cybersecurity',
            'Security' => 'Cybersecurity',
            'Ethical Hacking' => 'Cybersecurity',
            'Penetration Testing' => 'Cybersecurity',
            
            // Cloud Computing
            'AWS' => 'Cloud Computing',
            'Azure' => 'Cloud Computing',
            'Google Cloud' => 'Cloud Computing',
            'DevOps' => 'Cloud Computing',
            'Docker' => 'Cloud Computing',
            'Kubernetes' => 'Cloud Computing',
            
            // Database Management
            'SQL' => 'Database Management',
            'MongoDB' => 'Database Management',
            'PostgreSQL' => 'Database Management',
            'MySQL' => 'Database Management',
            'Database' => 'Database Management',
            
            // Programming Languages
            'C#' => 'Computer Science',
            'Ruby' => 'Computer Science',
            'Rails' => 'Computer Science',
            'Go Programming' => 'Computer Science',
            'Golang' => 'Computer Science',
            'Rust' => 'Computer Science',
            'C++' => 'Computer Science',
            
            // Design
            'UI Design' => 'Design',
            'UX Design' => 'Design',
            'Figma' => 'Design',
            'Adobe' => 'Design',
            'Photoshop' => 'Graphic Design',
            'Illustrator' => 'Graphic Design',
            
            // Marketing
            'Marketing' => 'Marketing',
            'SEO' => 'Marketing',
            'Social Media' => 'Marketing',
            'Content Marketing' => 'Marketing',
            'Email Marketing' => 'Marketing',
            
            // Business
            'Business' => 'Business Management',
            'Entrepreneurship' => 'Entrepreneurship',
            'Finance' => 'Finance',
            'Economics' => 'Economics',
            
            // Photography & Video
            'Photography' => 'Photography',
            'Video' => 'Video Production',
            'Filmmaking' => 'Video Production',
            
            // Music
            'Music' => 'Music',
            'Guitar' => 'Music',
            'Piano' => 'Music',
            
            // Writing
            'Writing' => 'Writing',
            'Content Writing' => 'Writing',
            'Copywriting' => 'Writing',
            'Blogging' => 'Writing',
            
            // Health & Fitness
            'Yoga' => 'Yoga',
            'Fitness' => 'Fitness Training',
            'Mental Health' => 'Mental Health',
            'Health' => 'Mental Health',
            'Nutrition' => 'Mental Health',
            
            // Art
            'Drawing' => 'Drawing & Painting',
            'Painting' => 'Drawing & Painting',
            'Art' => 'Drawing & Painting',
            
            // Test Prep
            'SAT' => 'SAT Preparation',
            'GRE' => 'GRE Preparation',
            'GMAT' => 'GMAT Preparation',
            'MCAT' => 'MCAT Preparation',
            'TOEFL' => 'TOEFL Preparation',
            'IELTS' => 'IELTS Preparation',
            
            // Personal Development
            'Public Speaking' => 'Public Speaking',
            'Time Management' => 'Time Management',
            'Career' => 'Career Coaching',
            'Leadership' => 'Career Coaching',
            
            // Science
            'Physics' => 'Physics',
            'Chemistry' => 'Chemistry',
            'Biology' => 'Biology',
            'Science' => 'Computer Science',
            
            // Math
            'Mathematics' => 'Mathematics',
            'Calculus' => 'Mathematics',
            'Algebra' => 'Mathematics',
            
            // Engineering
            'Electrical' => 'Electrical Engineering',
            'Mechanical' => 'Mechanical Engineering',
            'Civil' => 'Civil Engineering',
            'Engineering' => 'Computer Science',
            
            // Languages
            'English' => 'English',
            'French' => 'French',
            'Spanish' => 'Spanish',
            'German' => 'German',
            'Arabic' => 'Arabic',
            'Chinese' => 'Chinese',
            'Japanese' => 'Japanese',
            
            // Social Sciences
            'History' => 'History',
            'Geography' => 'Geography',
            'Philosophy' => 'Philosophy',
            'Psychology' => 'Psychology',
            'Sociology' => 'Sociology',
        ];
        
        // Get all courses
        $courses = $conn->fetchAllAssociative('SELECT c.id, c.title, c.category_id FROM course c');
        
        $updated = 0;
        foreach ($courses as $course) {
            $title = $course['title'];
            $currentCategoryId = $course['category_id'];
            $newCategory = null;
            
            // Find matching category based on title
            foreach ($courseCategoryMap as $keyword => $categoryName) {
                if (stripos($title, $keyword) !== false) {
                    $newCategory = $categoryName;
                    break;
                }
            }
            
            // If category found and different from current
            if ($newCategory && isset($categories[$newCategory])) {
                $newCategoryId = $categories[$newCategory];
                
                if ($newCategoryId != $currentCategoryId) {
                    $conn->executeStatement(
                        'UPDATE course SET category_id = ? WHERE id = ?',
                        [$newCategoryId, $course['id']]
                    );
                    $output->writeln("Updated: {$title} -> {$newCategory}");
                    $updated++;
                }
            }
        }
        
        $output->writeln("<info>Total courses reassigned: {$updated}</info>");
        return Command::SUCCESS;
    }
}

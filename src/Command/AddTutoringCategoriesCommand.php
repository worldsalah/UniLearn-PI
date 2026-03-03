<?php

namespace App\Command;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:add-tutoring-categories',
    description: 'Add tutoring subject categories to the database',
)]
class AddTutoringCategoriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categories = [
            // STEM - Science
            ['name' => 'Mathematics', 'description' => 'Algebra, Calculus, Statistics, Geometry', 'icon' => 'fa-calculator', 'color' => '#3B82F6'],
            ['name' => 'Physics', 'description' => 'Mechanics, Thermodynamics, Quantum Physics', 'icon' => 'fa-atom', 'color' => '#8B5CF6'],
            ['name' => 'Chemistry', 'description' => 'Organic, Inorganic, Biochemistry', 'icon' => 'fa-flask', 'color' => '#10B981'],
            ['name' => 'Biology', 'description' => 'Molecular Biology, Genetics, Ecology', 'icon' => 'fa-dna', 'color' => '#059669'],
            ['name' => 'Computer Science', 'description' => 'Programming, Algorithms, Data Structures', 'icon' => 'fa-laptop-code', 'color' => '#6366F1'],
            
            // Programming & Technology
            ['name' => 'Web Development', 'description' => 'HTML, CSS, JavaScript, React, Vue', 'icon' => 'fa-code', 'color' => '#EC4899'],
            ['name' => 'Mobile Development', 'description' => 'iOS, Android, React Native, Flutter', 'icon' => 'fa-mobile-alt', 'color' => '#F59E0B'],
            ['name' => 'Python Programming', 'description' => 'Python basics, Django, Flask, Data Science', 'icon' => 'fa-python', 'color' => '#3776AB'],
            ['name' => 'Java Programming', 'description' => 'Java SE, Java EE, Spring Framework', 'icon' => 'fa-java', 'color' => '#ED8B00'],
            ['name' => 'Data Science', 'description' => 'Machine Learning, AI, Data Analysis', 'icon' => 'fa-chart-line', 'color' => '#EF4444'],
            ['name' => 'Cybersecurity', 'description' => 'Network Security, Ethical Hacking', 'icon' => 'fa-shield-alt', 'color' => '#DC2626'],
            ['name' => 'Cloud Computing', 'description' => 'AWS, Azure, Google Cloud', 'icon' => 'fa-cloud', 'color' => '#0EA5E9'],
            ['name' => 'Database Management', 'description' => 'SQL, MySQL, PostgreSQL, MongoDB', 'icon' => 'fa-database', 'color' => '#7C3AED'],
            
            // Languages
            ['name' => 'English', 'description' => 'ESL, TOEFL, IELTS, Business English', 'icon' => 'fa-language', 'color' => '#2563EB'],
            ['name' => 'French', 'description' => 'DELF, DALF, French for beginners', 'icon' => 'fa-language', 'color' => '#7C3AED'],
            ['name' => 'Spanish', 'description' => 'DELE, Spanish for beginners', 'icon' => 'fa-language', 'color' => '#F59E0B'],
            ['name' => 'German', 'description' => 'Goethe, German for beginners', 'icon' => 'fa-language', 'color' => '#059669'],
            ['name' => 'Arabic', 'description' => 'Modern Standard Arabic, Dialects', 'icon' => 'fa-language', 'color' => '#10B981'],
            ['name' => 'Chinese', 'description' => 'Mandarin, HSK preparation', 'icon' => 'fa-language', 'color' => '#DC2626'],
            ['name' => 'Japanese', 'description' => 'JLPT preparation, Japanese culture', 'icon' => 'fa-language', 'color' => '#EC4899'],
            
            // Business & Finance
            ['name' => 'Business Management', 'description' => 'Leadership, Strategy, Operations', 'icon' => 'fa-briefcase', 'color' => '#4B5563'],
            ['name' => 'Marketing', 'description' => 'Digital Marketing, SEO, Social Media', 'icon' => 'fa-bullhorn', 'color' => '#F97316'],
            ['name' => 'Finance', 'description' => 'Investment, Accounting, Financial Analysis', 'icon' => 'fa-chart-pie', 'color' => '#22C55E'],
            ['name' => 'Economics', 'description' => 'Micro/Macro Economics, Econometrics', 'icon' => 'fa-coins', 'color' => '#EAB308'],
            ['name' => 'Entrepreneurship', 'description' => 'Startup, Business Planning', 'icon' => 'fa-rocket', 'color' => '#EF4444'],
            
            // Arts & Design
            ['name' => 'Graphic Design', 'description' => 'Adobe Suite, UI/UX, Branding', 'icon' => 'fa-palette', 'color' => '#EC4899'],
            ['name' => 'Music', 'description' => 'Piano, Guitar, Violin, Music Theory', 'icon' => 'fa-music', 'color' => '#8B5CF6'],
            ['name' => 'Photography', 'description' => 'Digital Photography, Editing', 'icon' => 'fa-camera', 'color' => '#6B7280'],
            ['name' => 'Video Production', 'description' => 'Filmmaking, Video Editing', 'icon' => 'fa-video', 'color' => '#DC2626'],
            ['name' => 'Drawing & Painting', 'description' => 'Sketching, Watercolor, Oil Painting', 'icon' => 'fa-paint-brush', 'color' => '#F59E0B'],
            
            // Test Preparation
            ['name' => 'SAT Preparation', 'description' => 'SAT Math, Reading, Writing', 'icon' => 'fa-graduation-cap', 'color' => '#3B82F6'],
            ['name' => 'GRE Preparation', 'description' => 'GRE Quantitative, Verbal', 'icon' => 'fa-graduation-cap', 'color' => '#6366F1'],
            ['name' => 'GMAT Preparation', 'description' => 'GMAT Quantitative, Verbal, IR', 'icon' => 'fa-graduation-cap', 'color' => '#8B5CF6'],
            ['name' => 'MCAT Preparation', 'description' => 'Medical College Admission Test', 'icon' => 'fa-graduation-cap', 'color' => '#059669'],
            ['name' => 'TOEFL Preparation', 'description' => 'Test of English as Foreign Language', 'icon' => 'fa-graduation-cap', 'color' => '#2563EB'],
            ['name' => 'IELTS Preparation', 'description' => 'International English Language Test', 'icon' => 'fa-graduation-cap', 'color' => '#DC2626'],
            
            // Personal Development
            ['name' => 'Public Speaking', 'description' => 'Presentation skills, Communication', 'icon' => 'fa-microphone', 'color' => '#F59E0B'],
            ['name' => 'Time Management', 'description' => 'Productivity, Organization', 'icon' => 'fa-clock', 'color' => '#10B981'],
            ['name' => 'Career Coaching', 'description' => 'Resume, Interview, Career Planning', 'icon' => 'fa-user-tie', 'color' => '#4B5563'],
            
            // Health & Fitness
            ['name' => 'Yoga', 'description' => 'Hatha, Vinyasa, Meditation', 'icon' => 'fa-spa', 'color' => '#10B981'],
            ['name' => 'Fitness Training', 'description' => 'Personal Training, Nutrition', 'icon' => 'fa-dumbbell', 'color' => '#EF4444'],
            ['name' => 'Mental Health', 'description' => 'Stress Management, Mindfulness', 'icon' => 'fa-brain', 'color' => '#8B5CF6'],
            
            // Academic Subjects
            ['name' => 'History', 'description' => 'World History, US History, European History', 'icon' => 'fa-landmark', 'color' => '#78350F'],
            ['name' => 'Geography', 'description' => 'Physical Geography, Human Geography', 'icon' => 'fa-globe', 'color' => '#0EA5E9'],
            ['name' => 'Philosophy', 'description' => 'Ethics, Logic, Political Philosophy', 'icon' => 'fa-book', 'color' => '#6B7280'],
            ['name' => 'Psychology', 'description' => 'Clinical, Cognitive, Developmental', 'icon' => 'fa-brain', 'color' => '#EC4899'],
            ['name' => 'Sociology', 'description' => 'Social Theory, Research Methods', 'icon' => 'fa-users', 'color' => '#7C3AED'],
            
            // Engineering
            ['name' => 'Electrical Engineering', 'description' => 'Circuits, Electronics, Power Systems', 'icon' => 'fa-bolt', 'color' => '#F59E0B'],
            ['name' => 'Mechanical Engineering', 'description' => 'Thermodynamics, Mechanics, CAD', 'icon' => 'fa-cogs', 'color' => '#6B7280'],
            ['name' => 'Civil Engineering', 'description' => 'Structures, Construction, Surveying', 'icon' => 'fa-building', 'color' => '#78350F'],
        ];

        $addedCount = 0;
        $existingCount = 0;

        foreach ($categories as $categoryData) {
            // Check if category already exists
            $existing = $this->entityManager->getRepository(Category::class)
                ->findOneBy(['name' => $categoryData['name']]);

            if ($existing !== null) {
                $existingCount++;
                continue;
            }

            $category = new Category();
            $category->setName($categoryData['name']);
            $category->setDescription($categoryData['description']);
            $category->setIcon($categoryData['icon']);
            $category->setColor($categoryData['color']);
            $category->setIsActive(true);

            $this->entityManager->persist($category);
            $addedCount++;
        }

        $this->entityManager->flush();

        $output->writeln([
            '',
            '<info>=====================================</info>',
            '<info>   Tutoring Categories Added!</info>',
            '<info>=====================================</info>',
            '',
            "<comment>Categories added:</comment> $addedCount",
            "<comment>Categories already existing:</comment> $existingCount",
            "<comment>Total processed:</comment> " . ($addedCount + $existingCount),
            '',
        ]);

        return Command::SUCCESS;
    }
}

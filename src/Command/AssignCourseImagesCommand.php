<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AssignCourseImagesCommand extends Command
{
    protected static $defaultName = 'app:assign-course-images';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this->setDescription('Assign unique real images to each course based on its topic');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->em->getConnection();
        
        // Course-specific images based on title keywords
        $courseImages = [
            // Web Development
            'React' => 'https://images.unsplash.com/photo-1633356122544-f60328f9a8b0?w=800&q=80',
            'Angular' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8d?w=800&q=80',
            'Vue' => 'https://images.unsplash.com/photo-1537432376769-00f5c2b4c0d5?w=800&q=80',
            'JavaScript' => 'https://images.unsplash.com/photo-1579463148229-1076f40a1ebf?w=800&q=80',
            'TypeScript' => 'https://images.unsplash.com/photo-1516116216623-08e3c3c5c3e8?w=800&q=80',
            'Node' => 'https://images.unsplash.com/photo-1628744928857-f6e3a9d0e4be?w=800&q=80',
            'Python' => 'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=800&q=80',
            'Django' => 'https://images.unsplash.com/photo-1598724066436-61195801dcdc?w=800&q=80',
            'PHP' => 'https://images.unsplash.com/photo-1555949963-ff9fe0c8700b?w=800&q=80',
            'Laravel' => 'https://images.unsplash.com/photo-1542831371-29b03574b96e?w=800&q=80',
            'HTML' => 'https://images.unsplash.com/photo-1621839673705-6617adf460c7?w=800&q=80',
            'CSS' => 'https://images.unsplash.com/photo-1507721999472-8f56337fb5e0?w=800&q=80',
            'Full Stack' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80',
            'Frontend' => 'https://images.unsplash.com/photo-1547658719-da2b51169166?w=800&q=80',
            'Backend' => 'https://images.unsplash.com/photo-1558494949-ef010cb5fac8?w=800&q=80',
            'Web Development Fundamentals' => 'https://images.unsplash.com/photo-1498050108023-c5249f88b5f0?w=800&q=80',
            'Web Development Bootcamp' => 'https://images.unsplash.com/photo-1517694712202-14dd7831606b?w=800&q=80',
            'Modern Web Development' => 'https://images.unsplash.com/photo-1547658719-da2b51169166?w=800&q=80',
            
            // Programming Languages
            'C#' => 'https://images.unsplash.com/photo-1618399792526-9a52b5d0d6c3?w=800&q=80',
            'Ruby' => 'https://images.unsplash.com/photo-1618399812474-7b6e8aebf1b?w=800&q=80',
            'Rails' => 'https://images.unsplash.com/photo-1618399812474-7b6e8aebf1b?w=800&q=80',
            'Go Programming' => 'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=800&q=80',
            'Golang' => 'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=800&q=80',
            
            // Data Science & AI
            'Machine Learning' => 'https://images.unsplash.com/photo-1555949963-aa891c9e6a8c?w=800&q=80',
            'Data Science' => 'https://images.unsplash.com/photo-1551288049-bebda4e5c2b5?w=800&q=80',
            'Deep Learning' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&q=80',
            'Neural Networks' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&q=80',
            'Natural Language' => 'https://images.unsplash.com/photo-1516321318423-f06f85e8a5e6?w=800&q=80',
            'Computer Vision' => 'https://images.unsplash.com/photo-1555949963-aa891c9e6a8c?w=800&q=80',
            'TensorFlow' => 'https://images.unsplash.com/photo-1555949963-aa891c9e6a8c?w=800&q=80',
            'Keras' => 'https://images.unsplash.com/photo-1555949963-aa891c9e6a8c?w=800&q=80',
            'Statistics' => 'https://images.unsplash.com/photo-1635070041078-52a287253c98?w=800&q=80',
            'AI Ethics' => 'https://images.unsplash.com/photo-1676299082778-df66ac9da7be?w=800&q=80',
            'AI' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&q=80',
            
            // Design
            'UI' => 'https://images.unsplash.com/photo-1561070791-2536199aceb?w=800&q=80',
            'UX' => 'https://images.unsplash.com/photo-1581291518633-83b67a8e4de7?w=800&q=80',
            'Graphic Design' => 'https://images.unsplash.com/photo-1626785774573-4b9a6ee6d28f?w=800&q=80',
            'Web Design' => 'https://images.unsplash.com/photo-1559028006-848865316f3c?w=800&q=80',
            'Figma' => 'https://images.unsplash.com/photo-1561070791-2536199aceb?w=800&q=80',
            'Adobe' => 'https://images.unsplash.com/photo-1626785774573-4b9a6ee6d28f?w=800&q=80',
            
            // Business & Marketing
            'Marketing' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80',
            'Digital Marketing' => 'https://images.unsplash.com/photo-1432888622747-4eb9a8efeb07?w=800&q=80',
            'SEO' => 'https://images.unsplash.com/photo-1432888622747-4eb9a8efeb07?w=800&q=80',
            'Social Media' => 'https://images.unsplash.com/photo-1611162617474-1a2d6c4ec1d0?w=800&q=80',
            'Business' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&q=80',
            'Entrepreneurship' => 'https://images.unsplash.com/photo-1556761175-5971dcf6c6e0?w=800&q=80',
            'Finance' => 'https://images.unsplash.com/photo-1611974789851-7126e9833461?w=800&q=80',
            'Economics' => 'https://images.unsplash.com/photo-1612228163561-cab5dd06d7df?w=800&q=80',
            
            // Photography & Video
            'Photography' => 'https://images.unsplash.com/photo-1542038784456-0f6a786f765b?w=800&q=80',
            'Video' => 'https://images.unsplash.com/photo-1492619372649-6304e2f8e3e5?w=800&q=80',
            'Video Production' => 'https://images.unsplash.com/photo-1492619372649-6304e2f8e3e5?w=800&q=80',
            'Filmmaking' => 'https://images.unsplash.com/photo-1485846234645-a626fd7a8b5b?w=800&q=80',
            
            // Music & Audio
            'Music' => 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=800&q=80',
            'Audio' => 'https://images.unsplash.com/photo-1598488061068-1f3e6688bdf7?w=800&q=80',
            'Guitar' => 'https://images.unsplash.com/photo-1510915361894-db525c4d38a1?w=800&q=80',
            'Piano' => 'https://images.unsplash.com/photo-1520523839897-bd7b7b7b7b7b?w=800&q=80',
            
            // Writing & Content
            'Writing' => 'https://images.unsplash.com/photo-1455390582262-ceb463f4af2d?w=800&q=80',
            'Content' => 'https://images.unsplash.com/photo-1455390582262-ceb463f4af2d?w=800&q=80',
            'Blogging' => 'https://images.unsplash.com/photo-1455390582262-ceb463f4af2d?w=800&q=80',
            'Copywriting' => 'https://images.unsplash.com/photo-1455390582262-ceb463f4af2d?w=800&q=80',
            
            // Health & Fitness
            'Yoga' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&q=80',
            'Fitness' => 'https://images.unsplash.com/photo-1534438320978-f3cc9afd1e2d?w=800&q=80',
            'Mental Health' => 'https://images.unsplash.com/photo-1548247416-fc2a2c5078c5?w=800&q=80',
            'Health' => 'https://images.unsplash.com/photo-1571019613454-1cb2f8f8f6e5?w=800&q=80',
            'Nutrition' => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=800&q=80',
            
            // Art & Creativity
            'Drawing' => 'https://images.unsplash.com/photo-1513364776137-1a538b6c61d3?w=800&q=80',
            'Painting' => 'https://images.unsplash.com/photo-1513364776137-1a538b6c61d3?w=800&q=80',
            'Art' => 'https://images.unsplash.com/photo-1513364776137-1a538b6c61d3?w=800&q=80',
            'Illustration' => 'https://images.unsplash.com/photo-1513364776137-1a538b6c61d3?w=800&q=80',
            
            // Education & Test Prep
            'SAT' => 'https://images.unsplash.com/photo-1434030216411-57b8d5a8b2f6?w=800&q=80',
            'GRE' => 'https://images.unsplash.com/photo-1456513080544-1e366b82c96c?w=800&q=80',
            'GMAT' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc9c8c0?w=800&q=80',
            'MCAT' => 'https://images.unsplash.com/photo-1532094349884-51686ec0c2d3?w=800&q=80',
            'TOEFL' => 'https://images.unsplash.com/photo-1546410597-7b34a6e0c52e?w=800&q=80',
            'IELTS' => 'https://images.unsplash.com/photo-1503676260728-5cc5d68c2e5f?w=800&q=80',
            
            // Personal Development
            'Public Speaking' => 'https://images.unsplash.com/photo-1475733538803-91a4a219a6db?w=800&q=80',
            'Time Management' => 'https://images.unsplash.com/photo-1506784983877-45594efa8c2a?w=800&q=80',
            'Career' => 'https://images.unsplash.com/photo-1521737711867-e3b9716f8f8d?w=800&q=80',
            'Leadership' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&q=80',
            
            // Science & Math
            'Science' => 'https://images.unsplash.com/photo-1532094349884-51686ec0c2d3?w=800&q=80',
            'Mathematics' => 'https://images.unsplash.com/photo-1635070041078-52a287253c98?w=800&q=80',
            'Physics' => 'https://images.unsplash.com/photo-1635070041078-52a287253c98?w=800&q=80',
            'Chemistry' => 'https://images.unsplash.com/photo-1532094349884-51686ec0c2d3?w=800&q=80',
            'Biology' => 'https://images.unsplash.com/photo-1530026405186-ed1bd13977e0?w=800&q=80',
            
            // Engineering
            'Engineering' => 'https://images.unsplash.com/photo-1581092921461-eab62e97a1d1?w=800&q=80',
            'Electrical' => 'https://images.unsplash.com/photo-1517077305552-67c9a13f5f3d?w=800&q=80',
            'Mechanical' => 'https://images.unsplash.com/photo-1581092921461-eab62e97a1d1?w=800&q=80',
            'Civil' => 'https://images.unsplash.com/photo-1504307652540-8e39dfdb6d00?w=800&q=80',
            
            // Languages
            'Language' => 'https://images.unsplash.com/photo-1546410597-7b34a6e0c52e?w=800&q=80',
            'English' => 'https://images.unsplash.com/photo-1546410597-7b34a6e0c52e?w=800&q=80',
            'Spanish' => 'https://images.unsplash.com/photo-1534274988756-a8bfce25a61e?w=800&q=80',
            'French' => 'https://images.unsplash.com/photo-1502607090315-6f96c7964c4d?w=800&q=80',
            
            // History & Social Sciences
            'History' => 'https://images.unsplash.com/photo-1461360370893-905b514cb970?w=800&q=80',
            'Geography' => 'https://images.unsplash.com/photo-1524661135-423995f229b4?w=800&q=80',
            'Philosophy' => 'https://images.unsplash.com/photo-1456586769316-0c936b9bbfe2?w=800&q=80',
            'Psychology' => 'https://images.unsplash.com/photo-1559753173-eeb0ff1ea204?w=800&q=80',
            'Sociology' => 'https://images.unsplash.com/photo-1529157105550-6c1c17c8b26a?w=800&q=80',
        ];
        
        // Get all courses
        $courses = $conn->fetchAllAssociative('SELECT c.id, c.title, cat.name as category FROM course c LEFT JOIN category cat ON c.category_id = cat.id');
        
        $updated = 0;
        foreach ($courses as $course) {
            $title = $course['title'];
            $category = $course['category'];
            $imageUrl = null;
            
            // Try to match by title keywords
            foreach ($courseImages as $keyword => $url) {
                if (stripos($title, $keyword) !== false) {
                    $imageUrl = $url;
                    break;
                }
            }
            
            // If no match found, use category-based image
            if (!$imageUrl && $category) {
                $categoryLower = strtolower($category);
                if (isset($courseImages[$category])) {
                    $imageUrl = $courseImages[$category];
                } elseif (strpos($categoryLower, 'web') !== false || strpos($categoryLower, 'programming') !== false) {
                    $imageUrl = 'https://images.unsplash.com/photo-1593720213428-28a5b9e94613?w=800&q=80';
                } elseif (strpos($categoryLower, 'design') !== false || strpos($categoryLower, 'graphic') !== false) {
                    $imageUrl = 'https://images.unsplash.com/photo-1561070791-2536199aceb?w=800&q=80';
                } elseif (strpos($categoryLower, 'data') !== false || strpos($categoryLower, 'ai') !== false) {
                    $imageUrl = 'https://images.unsplash.com/photo-1551288049-bebda4e5c2b5?w=800&q=80';
                } elseif (strpos($categoryLower, 'business') !== false || strpos($categoryLower, 'marketing') !== false) {
                    $imageUrl = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&q=80';
                } elseif (strpos($categoryLower, 'writing') !== false) {
                    $imageUrl = 'https://images.unsplash.com/photo-1455390582262-ceb463f4af2d?w=800&q=80';
                } else {
                    // Default education image
                    $imageUrl = 'https://images.unsplash.com/photo-1516321318423-f06f85e8a5e6?w=800&q=80';
                }
            }
            
            if ($imageUrl) {
                $conn->executeStatement('UPDATE course SET thumbnail_url = ? WHERE id = ?', [$imageUrl, $course['id']]);
                $output->writeln("Updated: {$title} -> {$imageUrl}");
                $updated++;
            }
        }
        
        $output->writeln("<info>Total courses updated: {$updated}</info>");
        return Command::SUCCESS;
    }
}

<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCourseThumbnailsCommand extends Command
{
    protected static $defaultName = 'app:update-course-thumbnails';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this->setDescription('Update course thumbnails with real photos based on category');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categoryImages = [
            'Web Development' => 'https://images.unsplash.com/photo-1593720213428-28a5b9e94613?w=800&q=80',
            'Design' => 'https://images.unsplash.com/photo-1561070791-2536199aceb?w=800&q=80',
            'Business' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&q=80',
            'Marketing' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80',
            'Photography' => 'https://images.unsplash.com/photo-1542038784456-0f6a786f765b?w=800&q=80',
            'Music' => 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=800&q=80',
            'Writing' => 'https://images.unsplash.com/photo-1455390582262-ceb463f4af2d?w=800&q=80',
            'Programming' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8d?w=800&q=80',
            'Data Science' => 'https://images.unsplash.com/photo-1551288049-bebda4e5c2b5?w=800&q=80',
            'Finance' => 'https://images.unsplash.com/photo-1611974789851-7126e9833461?w=800&q=80',
            'Economics' => 'https://images.unsplash.com/photo-1612228163561-cab5dd06d7df?w=800&q=80',
            'Entrepreneurship' => 'https://images.unsplash.com/photo-1556761175-5971dcf6c6e0?w=800&q=80',
            'Graphic Design' => 'https://images.unsplash.com/photo-1626785774573-4b9a6ee6d28f?w=800&q=80',
            'Video Production' => 'https://images.unsplash.com/photo-1492619372649-6304e2f8e3e5?w=800&q=80',
            'Drawing & Painting' => 'https://images.unsplash.com/photo-1513364776137-1a538b6c61d3?w=800&q=80',
            'SAT Preparation' => 'https://images.unsplash.com/photo-1434030216411-57b8d5a8b2f6?w=800&q=80',
            'GRE Preparation' => 'https://images.unsplash.com/photo-1456513080544-1e366b82c96c?w=800&q=80',
            'GMAT Preparation' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc9c8c0?w=800&q=80',
            'MCAT Preparation' => 'https://images.unsplash.com/photo-1532094349884-51686ec0c2d3?w=800&q=80',
            'TOEFL Preparation' => 'https://images.unsplash.com/photo-1546410597-7b34a6e0c52e?w=800&q=80',
            'IELTS Preparation' => 'https://images.unsplash.com/photo-1503676260728-5cc5d68c2e5f?w=800&q=80',
            'Public Speaking' => 'https://images.unsplash.com/photo-1475733538803-91a4a219a6db?w=800&q=80',
            'Time Management' => 'https://images.unsplash.com/photo-1506784983877-45594efa8c2a?w=800&q=80',
            'Career Coaching' => 'https://images.unsplash.com/photo-1521737711867-e3b9716f8f8d?w=800&q=80',
            'Yoga' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&q=80',
            'Fitness Training' => 'https://images.unsplash.com/photo-1534438320978-f3cc9afd1e2d?w=800&q=80',
            'Mental Health' => 'https://images.unsplash.com/photo-1548247416-fc2a2c5078c5?w=800&q=80',
            'History' => 'https://images.unsplash.com/photo-1461360370893-905b514cb970?w=800&q=80',
            'Geography' => 'https://images.unsplash.com/photo-1524661135-423995f229b4?w=800&q=80',
            'Philosophy' => 'https://images.unsplash.com/photo-1456586769316-0c936b9bbfe2?w=800&q=80',
            'Psychology' => 'https://images.unsplash.com/photo-1559753173-eeb0ff1ea204?w=800&q=80',
            'Sociology' => 'https://images.unsplash.com/photo-1529157105550-6c1c17c8b26a?w=800&q=80',
            'Electrical Engineering' => 'https://images.unsplash.com/photo-1517077305552-67c9a13f5f3d?w=800&q=80',
            'Mechanical Engineering' => 'https://images.unsplash.com/photo-1581092921461-eab62e97a1d1?w=800&q=80',
            'Civil Engineering' => 'https://images.unsplash.com/photo-1504307652540-8e39dfdb6d00?w=800&q=80',
            'Language Learning' => 'https://images.unsplash.com/photo-1546410597-7b34a6e0c52e?w=800&q=80',
            'Health & Wellness' => 'https://images.unsplash.com/photo-1571019613454-1cb2f8f8f6e5?w=800&q=80',
            'Art & Creativity' => 'https://images.unsplash.com/photo-1513364776137-1a538b6c61d3?w=800&q=80',
            'Science' => 'https://images.unsplash.com/photo-1532094349884-51686ec0c2d3?w=800&q=80',
            'Mathematics' => 'https://images.unsplash.com/photo-1635070041078-52a287253c98?w=800&q=80',
        ];

        $defaultImage = 'https://images.unsplash.com/photo-1516321318423-f06f85e8a5e6?w=800&q=80';

        $conn = $this->em->getConnection();
        $updated = 0;

        foreach ($categoryImages as $categoryName => $imageUrl) {
            $sql = "UPDATE course c 
                    INNER JOIN category cat ON c.category_id = cat.id 
                    SET c.thumbnail_url = :url 
                    WHERE cat.name = :categoryName 
                    AND (c.thumbnail_url IS NULL OR c.thumbnail_url LIKE '%/assets/images%' OR c.thumbnail_url = '')";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeStatement(['url' => $imageUrl, 'categoryName' => $categoryName]);
            $updated += $result;
            $output->writeln("Updated $result courses for category: $categoryName");
        }

        // Default for remaining courses
        $sql = "UPDATE course SET thumbnail_url = :url WHERE thumbnail_url IS NULL OR thumbnail_url = ''";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeStatement(['url' => $defaultImage]);
        $updated += $result;
        $output->writeln("Updated $result remaining courses with default image");

        $output->writeln("<info>Total courses updated: $updated</info>");
        return Command::SUCCESS;
    }
}

<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use App\Entity\Job;
use App\Entity\Category;
use App\Entity\User;

#[AsCommand(
    name: 'app:create-sample-data',
    description: 'Creates sample products and jobs for testing'
)]
class CreateSampleDataCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get users to assign as freelancers/clients
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        if (empty($users)) {
            $output->writeln('<error>No users found. Please create users first.</error>');
            return Command::FAILURE;
        }

        // Get categories
        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        
        if (empty($categories)) {
            $output->writeln('<error>No categories found. Please run app:create-categories first.</error>');
            return Command::FAILURE;
        }

        // Create sample products
        $sampleProducts = [
            [
                'title' => 'Web Development Service',
                'description' => 'Professional web development using modern technologies like React, Vue.js, and Laravel. I create responsive, fast, and secure websites tailored to your business needs.',
                'price' => 1500.00,
                'category' => $categories[0], // Web Development
            ],
            [
                'title' => 'UI/UX Design Package',
                'description' => 'Complete UI/UX design service including wireframes, mockups, and prototypes. I focus on user-centered design to create intuitive and beautiful interfaces.',
                'price' => 800.00,
                'category' => $categories[1], // Design
            ],
            [
                'title' => 'Digital Marketing Campaign',
                'description' => 'Comprehensive digital marketing services including SEO, social media management, and content marketing to grow your online presence.',
                'price' => 1200.00,
                'category' => $categories[2], // Marketing
            ],
            [
                'title' => 'Content Writing Service',
                'description' => 'Professional content writing for blogs, websites, and marketing materials. SEO-optimized, engaging, and tailored to your brand voice.',
                'price' => 500.00,
                'category' => $categories[3], // Writing
            ],
        ];

        foreach ($sampleProducts as $productData) {
            $product = new Product();
            $product->setTitle($productData['title']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setCategory($productData['category']);
            $product->setFreelancer($users[array_rand($users)]);
            
            $this->entityManager->persist($product);
            $output->writeln('<info>Created product: ' . $productData['title'] . '</info>');
        }

        // Create sample jobs
        $sampleJobs = [
            [
                'title' => 'Senior Full Stack Developer Needed',
                'description' => 'We are looking for an experienced full stack developer to join our team. Must have experience with React, Node.js, and cloud services.',
                'budget' => 8000.00,
                'location' => 'Remote',
                'type' => 'full-time',
                'experienceLevel' => 'senior',
                'duration' => '6 months',
                'requirements' => '5+ years of experience, React, Node.js, AWS, strong communication skills',
                'skills' => 'React, Node.js, JavaScript, AWS, Git',
            ],
            [
                'title' => 'WordPress Website Development',
                'description' => 'Need a professional WordPress website for a small business. Must include e-commerce functionality and responsive design.',
                'budget' => 2500.00,
                'location' => 'New York, NY',
                'type' => 'contract',
                'experienceLevel' => 'mid',
                'duration' => '2 months',
                'requirements' => 'WordPress experience, WooCommerce knowledge, PHP, CSS',
                'skills' => 'WordPress, PHP, CSS, WooCommerce, MySQL',
            ],
            [
                'title' => 'Content Creator for YouTube Channel',
                'description' => 'Looking for creative content creators to produce engaging videos for our tech YouTube channel. Experience with video editing required.',
                'budget' => 1500.00,
                'location' => 'Remote',
                'type' => 'freelance',
                'experienceLevel' => 'entry',
                'duration' => '3 months',
                'requirements' => 'Video editing skills, creativity, reliable internet connection',
                'skills' => 'Video Editing, Content Creation, YouTube, Social Media',
            ],
        ];

        foreach ($sampleJobs as $jobData) {
            $job = new Job();
            $job->setTitle($jobData['title']);
            $job->setDescription($jobData['description']);
            $job->setBudget($jobData['budget']);
            $job->setLocation($jobData['location']);
            $job->setType($jobData['type']);
            $job->setExperienceLevel($jobData['experienceLevel']);
            $job->setDuration($jobData['duration']);
            $job->setRequirements($jobData['requirements']);
            $job->setSkills($jobData['skills']);
            $job->setClient($users[array_rand($users)]);
            
            $this->entityManager->persist($job);
            $output->writeln('<info>Created job: ' . $jobData['title'] . '</info>');
        }

        $this->entityManager->flush();

        $output->writeln('<success>Sample data created successfully!</success>');
        $output->writeln('<info>You can now add services and jobs using the admin panel.</info>');

        return Command::SUCCESS;
    }
}

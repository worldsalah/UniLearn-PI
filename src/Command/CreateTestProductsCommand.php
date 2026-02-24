<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-test-products',
    description: 'Creates test products for the marketplace'
)]
class CreateTestProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get an existing user for products
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'demo@unilearn.com']);
        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@unilearn.com']);
        }
        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'salahanez@gmail.com']);
        }
        if (!$user) {
            $output->writeln('<error>No users found in database. Please create a user first.</error>');
            return Command::FAILURE;
        }

        // Create categories if they don't exist
        $categories = ['Web Development', 'Design', 'Marketing', 'Writing', 'Consulting'];
        $categoryEntities = [];

        foreach ($categories as $categoryName) {
            $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $categoryName]);
            if (!$category) {
                $category = new Category();
                $category->setName($categoryName);
                $category->setSlug(strtolower(str_replace(' ', '-', $categoryName)));
                $category->setDescription($categoryName . ' services');
                $category->setIsActive(true);
                $this->entityManager->persist($category);
                $this->entityManager->flush();
            }
            $categoryEntities[$categoryName] = $category;
        }

        // Create test products
        $products = [
            [
                'title' => 'Professional Web Development',
                'description' => 'Build modern, responsive websites with cutting-edge technologies. Expert in React, Vue, and Angular. We create stunning websites that convert visitors into customers.',
                'price' => 299.99,
                'category' => 'Web Development'
            ],
            [
                'title' => 'UI/UX Design Services',
                'description' => 'Create beautiful, user-friendly interfaces that delight your customers and drive conversions. Professional design that makes your brand stand out.',
                'price' => 199.99,
                'category' => 'Design'
            ],
            [
                'title' => 'Digital Marketing Strategy',
                'description' => 'Comprehensive digital marketing campaigns that increase brand awareness and drive sales. Expert in SEO, social media, and content marketing.',
                'price' => 399.99,
                'category' => 'Marketing'
            ],
            [
                'title' => 'Content Writing Services',
                'description' => 'High-quality, SEO-optimized content that engages your audience and ranks well. Professional writing that delivers results.',
                'price' => 149.99,
                'category' => 'Writing'
            ],
            [
                'title' => 'Business Consulting',
                'description' => 'Strategic business advice to help your company grow and succeed in competitive markets. Expert guidance for sustainable growth.',
                'price' => 499.99,
                'category' => 'Consulting'
            ]
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setTitle($productData['title']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setCategory($categoryEntities[$productData['category']]);
            $product->setFreelancer($user);
            
            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();

        $output->writeln('<info>Created ' . count($products) . ' test products successfully!</info>');
        return Command::SUCCESS;
    }
}

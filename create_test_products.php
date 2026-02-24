<?php

require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get(EntityManagerInterface::class);

// Get or create a default user for products
$user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
if (!$user) {
    $user = new User();
    $user->setEmail('test@example.com');
    $user->setFullName('Test User');
    $user->setPassword('password');
    $entityManager->persist($user);
    $entityManager->flush();
}

// Create categories if they don't exist
$categories = ['Web Development', 'Design', 'Marketing', 'Writing', 'Consulting'];
$categoryEntities = [];

foreach ($categories as $categoryName) {
    $category = $entityManager->getRepository(Category::class)->findOneBy(['name' => $categoryName]);
    if (!$category) {
        $category = new Category();
        $category->setName($categoryName);
        $category->setSlug(strtolower(str_replace(' ', '-', $categoryName)));
        $category->setDescription($categoryName . ' services');
        $category->setIsActive(true);
        $entityManager->persist($category);
        $entityManager->flush();
    }
    $categoryEntities[$categoryName] = $category;
}

// Create test products
$products = [
    [
        'title' => 'Professional Web Development',
        'description' => 'Build modern, responsive websites with cutting-edge technologies. Expert in React, Vue, and Angular.',
        'price' => 299.99,
        'category' => 'Web Development',
        'rating' => 4.8,
        'views' => 150,
        'orders' => 25
    ],
    [
        'title' => 'UI/UX Design Services',
        'description' => 'Create beautiful, user-friendly interfaces that delight your customers and drive conversions.',
        'price' => 199.99,
        'category' => 'Design',
        'rating' => 4.9,
        'views' => 200,
        'orders' => 30
    ],
    [
        'title' => 'Digital Marketing Strategy',
        'description' => 'Comprehensive digital marketing campaigns that increase brand awareness and drive sales.',
        'price' => 399.99,
        'category' => 'Marketing',
        'rating' => 4.7,
        'views' => 120,
        'orders' => 20
    ],
    [
        'title' => 'Content Writing Services',
        'description' => 'High-quality, SEO-optimized content that engages your audience and ranks well.',
        'price' => 149.99,
        'category' => 'Writing',
        'rating' => 4.6,
        'views' => 180,
        'orders' => 22
    ],
    [
        'title' => 'Business Consulting',
        'description' => 'Strategic business advice to help your company grow and succeed in competitive markets.',
        'price' => 499.99,
        'category' => 'Consulting',
        'rating' => 4.9,
        'views' => 100,
        'orders' => 15
    ]
];

foreach ($products as $productData) {
    $product = new Product();
    $product->setTitle($productData['title']);
    $product->setDescription($productData['description']);
    $product->setPrice($productData['price']);
    $product->setCategory($categoryEntities[$productData['category']]);
    $product->setFreelancer($user);
    $product->setRating($productData['rating']);
    $product->setViews($productData['views']);
    $product->setOrders($productData['orders']);
    $product->setCreatedAt(new \DateTime());
    $product->setUpdatedAt(new \DateTime());
    
    $entityManager->persist($product);
}

$entityManager->flush();

echo "Created " . count($products) . " test products successfully!\n";

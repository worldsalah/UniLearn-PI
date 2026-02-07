<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Student;
use App\Entity\Product;
use App\Entity\Job;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Create a Student/Freelancer for each service
        $scenarios = [
            [
                'email' => 'tutor@unilearn.com',
                'name' => 'Dr. Smith',
                'bio' => 'Expert in Mathematics and Physics with 10 years of teaching experience.',
                'service' => 'Advanced Calculus Tutoring',
                'category' => 'Tutor',
                'price' => 25.0,
                'desc' => 'Helping students prepare for final exams in Calculus and Algebra. Interactive sessions focused on problem-solving techniques and core concepts.',
                'rating' => 4.8
            ],
            [
                'email' => 'dev@unilearn.com',
                'name' => 'Alex Chen',
                'bio' => 'Senior Full Stack Developer specializing in Symfony and React.',
                'service' => 'Web Development Mentorship',
                'category' => 'Web Development',
                'price' => 50.0,
                'desc' => 'Teaching Symfony and React to undergraduate students. Get hands-on experience building real-world applications with modern tools.',
                'rating' => 4.9
            ],
            [
                'email' => 'admin@unilearn.com',
                'name' => 'Sarah Johnson',
                'bio' => 'Administrative professional with a passion for academic organization.',
                'service' => 'Academic Admin Support',
                'category' => 'Administrative',
                'price' => 15.0,
                'desc' => 'Managing the digital archive and assisting visitors. Professional support for research projects, thesis formatting, and data entry.',
                'rating' => 4.5
            ]
        ];

        foreach ($scenarios as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setRoles(['ROLE_USER', 'ROLE_FREELANCER']);
            $password = $this->hasher->hashPassword($user, 'password123');
            $user->setPassword($password);
            $manager->persist($user);

            $student = new Student();
            $student->setFullName($data['name']);
            $student->setBio($data['bio']);
            $student->setRating($data['rating']);
            $student->setUser($user);
            $manager->persist($student);

            $product = new Product();
            $product->setTitle($data['service']);
            $product->setCategory($data['category']);
            $product->setPrice($data['price']);
            $product->setDescription($data['desc']);
            $product->setFreelancer($student);
            $product->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($product);
        }

        // 2. Add some generic jobs
        $jobs = [
            ['title' => 'Need help with React Project', 'budget' => 200],
            ['title' => 'Calculus Assignment Support', 'budget' => 50],
            ['title' => 'Logo Design for Student Club', 'budget' => 80]
        ];

        // Create a generic user to post the jobs
        $posterUser = new User();
        $posterUser->setEmail('student@unilearn.com');
        $posterUser->setRoles(['ROLE_USER']);
        $posterUser->setPassword($this->hasher->hashPassword($posterUser, 'password123'));
        $manager->persist($posterUser);

        foreach ($jobs as $jobData) {
            $job = new Job();
            $job->setTitle($jobData['title']);
            $job->setBudget($jobData['budget']);
            $job->setDescription('Detailed requirements for ' . $jobData['title']);
            $job->setClient($posterUser);
            $manager->persist($job);
        }

        $manager->flush();
    }
}

<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchPageController extends AbstractController
{
    public function __construct(
        private CourseRepository $courseRepository,
        private UserRepository $userRepository,
        private ProductRepository $productRepository,
        private JobRepository $jobRepository
    ) {}

    #[Route('/search', name: 'app_search')]
    public function search(Request $request): Response
    {
        $query = trim($request->query->get('query', ''));
        $type = $request->query->get('type', 'all');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 12;

        $results = [
            'courses' => [],
            'users' => [],
            'products' => [],
            'jobs' => [],
            'total' => 0
        ];

        if (strlen($query) >= 2) {
            // Search courses
            if ($type === 'all' || $type === 'courses') {
                $courses = $this->courseRepository->createQueryBuilder('c')
                    ->where('c.title LIKE :query OR c.description LIKE :query OR c.shortDescription LIKE :query')
                    ->andWhere('c.status = :status')
                    ->setParameter('query', '%' . $query . '%')
                    ->setParameter('status', 'live')
                    ->setFirstResult(($page - 1) * $limit)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
                
                $results['courses'] = $courses;
                $results['total'] += count($courses);
            }

            // Search users
            if ($type === 'all' || $type === 'users') {
                $users = $this->userRepository->createQueryBuilder('u')
                    ->where('u.fullName LIKE :query OR u.email LIKE :query')
                    ->setParameter('query', '%' . $query . '%')
                    ->setMaxResults(10)
                    ->getQuery()
                    ->getResult();
                
                $results['users'] = $users;
                $results['total'] += count($users);
            }

            // Search products
            if ($type === 'all' || $type === 'products') {
                $products = $this->productRepository->createQueryBuilder('p')
                    ->where('p.title LIKE :query OR p.description LIKE :query')
                    ->setParameter('query', '%' . $query . '%')
                    ->setMaxResults(10)
                    ->getQuery()
                    ->getResult();
                
                $results['products'] = $products;
                $results['total'] += count($products);
            }

            // Search jobs
            if ($type === 'all' || $type === 'jobs') {
                $jobs = $this->jobRepository->createQueryBuilder('j')
                    ->where('j.title LIKE :query OR j.description LIKE :query')
                    ->setParameter('query', '%' . $query . '%')
                    ->setMaxResults(10)
                    ->getQuery()
                    ->getResult();
                
                $results['jobs'] = $jobs;
                $results['total'] += count($jobs);
            }
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'page' => $page
        ]);
    }
}

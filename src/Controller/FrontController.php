<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\JobRepository;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class FrontController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('marketplace/new_index.html.twig');
    }

    #[Route('/jobs', name: 'app_job_index')]
    public function jobs(JobRepository $jobRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $search = $request->query->get('search', '');
        
        $queryBuilder = $jobRepository->createQueryBuilder('j')
            ->leftJoin('j.applications', 'a')
            ->addSelect('COUNT(a.id) as applicationCount')
            ->groupBy('j.id');
        
        if ($search) {
            $queryBuilder
                ->leftJoin('j.client', 'c')
                ->where('j.title LIKE :search')
                ->orWhere('j.description LIKE :search')
                ->orWhere('c.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        $query = $queryBuilder->orderBy('j.createdAt', 'DESC')->getQuery();
        $jobs = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        // Add application count to each job entity
        foreach ($jobs as $job) {
            $job->applicationCount = $job['applicationCount'] ?? 0;
        }

        return $this->render('job/index.html.twig', [
            'jobs' => $jobs,
            'search' => $search,
        ]);
    }

    #[Route('/products', name: 'app_product_index')]
    public function products(ProductRepository $productRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $search = $request->query->get('search', '');
        
        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->where('p.deletedAt IS NULL');
        
        if ($search) {
            $queryBuilder
                ->where('p.title LIKE :search')
                ->orWhere('p.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        $query = $queryBuilder->orderBy('p.createdAt', 'DESC')->getQuery();
        $products = $paginator->paginate($query, $request->query->getInt('page', 1), 12);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('contact.html.twig');
    }

    #[Route('/how-it-works', name: 'app_how_it_works')]
    public function howItWorks(): Response
    {
        return $this->render('how-it-works.html.twig');
    }
}

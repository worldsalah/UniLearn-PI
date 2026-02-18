<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
// #[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(
        \App\Repository\StudentRepository $studentRepository,
        \App\Repository\ProductRepository $productRepository,
        \App\Repository\JobRepository $jobRepository,
        \App\Repository\OrderRepository $orderRepository,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'students' => $studentRepository->count([]),
                'products' => $productRepository->count(['deletedAt' => null]),
                'jobs' => $jobRepository->count(['deletedAt' => null]),
                'orders' => $orderRepository->count([]),
                'revenue' => $orderRepository->createQueryBuilder('o')
                    ->select('SUM(o.totalPrice)')
                    ->where('o.status = :status')
                    ->setParameter('status', 'completed')
                    ->getQuery()
                    ->getSingleScalarResult() ?: 0,
            ],
        ]);
    }
}

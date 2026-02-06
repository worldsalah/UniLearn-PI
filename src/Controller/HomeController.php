<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        $orders = $user ? $orderRepository->findBy(['buyer' => $user], ['createdAt' => 'DESC']) : [];

        return $this->render('dashboard/index.html.twig', [
            'orders' => $orders
        ]);
    }
}

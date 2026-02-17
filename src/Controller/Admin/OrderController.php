<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/order')]
// #[IsGranted('ROLE_ADMIN')]
class OrderController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/', name: 'app_admin_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.product', 'p')
            ->addSelect('o', 'p')
            ->where('p.deletedAt IS NULL OR p.deletedAt IS NOT NULL')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery();
        $orders = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'orderStats' => $this->getOrderStatistics(),
        ]);
    }

    #[Route('/new', name: 'app_admin_order_new', methods: ['GET', 'POST'], priority: 2)]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(\App\Form\Form\OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($order);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_show', methods: ['GET'])]
    public function show(OrderRepository $orderRepository, int $id): Response
    {
        $order = $orderRepository->find($id);
        
        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        $order = $orderRepository->find($id);
        
        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
        }
        
        $form = $this->createForm(\App\Form\Form\OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_order_delete', methods: ['POST'])]
    public function delete(Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        $order = $orderRepository->find($id);
        
        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
        }
        
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'Order deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Get order statistics for dashboard display
     */
    private function getOrderStatistics(): array
    {
        $orderRepository = $this->entityManager->getRepository(Order::class);
        
        // Total orders
        $totalOrders = $orderRepository->count([]);
        
        // Orders by status
        $pendingOrders = $orderRepository->count(['status' => 'pending']);
        $completedOrders = $orderRepository->count(['status' => 'completed']);
        $cancelledOrders = $orderRepository->count(['status' => 'cancelled']);
        
        // Total revenue (completed orders)
        $revenueQuery = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalPrice) as total')
            ->where('o.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery();
        $totalRevenue = $revenueQuery->getSingleScalarResult() ?: 0;
        
        // Recent orders (last 7 days)
        $sevenDaysAgo = new \DateTime('-7 days');
        $recentOrdersQuery = $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id) as count')
            ->where('o.createdAt >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery();
        $recentOrders = $recentOrdersQuery->getSingleScalarResult() ?: 0;
        
        return [
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
            'cancelledOrders' => $cancelledOrders,
            'totalRevenue' => $totalRevenue,
            'recentOrders' => $recentOrders,
        ];
    }
}

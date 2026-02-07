<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
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
    #[Route('/', name: 'app_admin_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $orderRepository->createQueryBuilder('o')->orderBy('o.createdAt', 'DESC')->getQuery();
        $orders = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(\App\Form\OrderType::class, $order);
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
    public function delete(Request $request, Order $order, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
    }
}

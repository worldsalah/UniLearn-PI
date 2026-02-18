<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Form\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }
        
        $order = new Order();
        $order->setProduct($product);
        $order->setBuyer($user instanceof \App\Entity\User ? $user : null);
        $order->setTotalPrice($product->getPrice());
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($order);
            $entityManager->flush();
            
            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        return $this->render('order/new.html.twig', [
            'form' => $form,
            'product' => $product
        ]);
    }

    #[Route('/{id}/complete', name: 'app_order_complete', methods: ['POST'])]
    public function complete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }
        
        if ($order->getBuyer() !== $user) {
            throw $this->createAccessDeniedException('You cannot complete this order');
        }

        $rating = $request->request->get('rating');
        $review = $request->request->get('review');

        $order->setStatus('completed');
        $order->setRating((int) $rating);
        $order->setReview($review);

        $entityManager->flush();

        $this->addFlash('success', 'Order completed successfully!');
        return $this->redirectToRoute('app_marketplace_index');
    }
}

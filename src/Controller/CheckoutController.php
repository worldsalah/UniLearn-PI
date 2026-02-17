<?php

namespace App\Controller;

use App\Entity\Checkout;
use App\Form\CheckoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    #[Route('/', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $checkout = new Checkout();
        $checkout->setTotalAmount(183.57); // Set default total amount
        
        $form = $this->createForm(CheckoutType::class, $checkout, [
            'total_amount' => 183.57
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // In a real application, you would process the payment here
            $entityManager->persist($checkout);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commande a été passée avec succès! Merci pour votre achat.');

            return $this->redirectToRoute('app_checkout', [], Response::HTTP_SEE_OTHER);
        }

        // Si le formulaire est soumis mais invalide, afficher les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('shop/checkout.html.twig', [
            'checkoutForm' => $form->createView(),
        ]);
    }
}

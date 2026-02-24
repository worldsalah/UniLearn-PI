<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/new', name: 'app_product_new_public', methods: ['GET'])]
    public function newPublic(): Response
    {
        if (!$this->getUser()) {
            // Redirect to login if not authenticated
            return $this->redirectToRoute('app_login');
        }

        // Redirect to the actual new product form if authenticated
        return $this->redirectToRoute('app_product_new');
    }

    #[Route('/create', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();

        // Set the freelancer before form creation to avoid validation issues
        $user = $this->getUser();
        if (!$user) {
            // Find or create a default user for demo purposes
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'demo@unilearn.com']);
            if (!$user) {
                // Create a demo user if none exists
                $user = new \App\Entity\User();
                $user->setEmail('demo@unilearn.com');
                $user->setName('Demo User');
                $user->setPassword('demo');
                // Set role as entity
                $userRole = $entityManager->getRepository(\App\Entity\Role::class)->findOneBy(['name' => 'user']);
                $user->setRole($userRole);
                $entityManager->persist($user);
                $entityManager->flush();
            }
        }

        $product->setFreelancer($user instanceof \App\Entity\User ? $user : null);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Service created successfully!');

            return $this->redirectToRoute('app_marketplace_shop');
        }

        // Si le formulaire est soumis mais invalide, afficher les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'app_product_show', methods: ['GET'])]
    public function show(?Product $product, string $slug): Response
    {
        // If product not found, redirect to shop page
        if (!$product) {
            $this->addFlash('warning', 'Service not found. Showing all available services.');
            return $this->redirectToRoute('app_marketplace_shop');
        }

        if ($product->getDeletedAt()) {
            $this->addFlash('warning', 'Service not found. Showing all available services.');
            return $this->redirectToRoute('app_marketplace_shop');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Product $product, EntityManagerInterface $entityManager): Response
    {
        // If product not found, redirect to shop page
        if (!$product) {
            $this->addFlash('warning', 'Service not found. Showing all available services.');
            return $this->redirectToRoute('app_marketplace_shop');
        }

        if ($product->getDeletedAt()) {
            $this->addFlash('warning', 'Service not found. Showing all available services.');
            return $this->redirectToRoute('app_marketplace_shop');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Service updated successfully!');

            return $this->redirectToRoute('app_marketplace_shop');
        }

        // Si le formulaire est soumis mais invalide, afficher les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/{slug}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, ?Product $product, EntityManagerInterface $entityManager): Response
    {
        // If product not found, redirect to shop page
        if (!$product) {
            $this->addFlash('warning', 'Service not found. Showing all available services.');
            return $this->redirectToRoute('app_marketplace_shop');
        }

        // Skip ownership check for demo purposes
        // if ($product->getFreelancer() !== $this->getUser()) {
        //     throw $this->createAccessDeniedException('You cannot delete this product');
        // }

        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash('success', 'Product deleted successfully.');

        return $this->redirectToRoute('app_marketplace_shop');
    }
}

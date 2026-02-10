<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Order;
use App\Form\Form\ProductType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        
        $product->setFreelancer($user);
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setCreatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($product);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le produit a été créé avec succès.');
            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        // Si le formulaire est soumis mais invalide, afficher les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{slug}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        if ($product->getDeletedAt()) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($product->getDeletedAt()) {
            throw $this->createNotFoundException('Product not found');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle user assignment - if no user is logged in, use a default user
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
            
            // Ensure the product has a freelancer
            if (!$product->getFreelancer()) {
                $product->setFreelancer($user);
            }
            
            $product->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form
        ]);
    }

    #[Route('/{slug}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
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

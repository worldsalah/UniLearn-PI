<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // If user is authenticated, redirect to welcome page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_welcome');
        }

        return $this->render('home/index.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        
        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/welcome', name: 'app_welcome')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function welcome(): Response
    {
        $user = $this->getUser();
        
        return $this->render('home/index-after-login.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/courses', name: 'app_courses')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function courses(): Response
    {
        $user = $this->getUser();
        
        return $this->render('courses/index.html.twig', [
            'user' => $user,
        ]);
    }
}

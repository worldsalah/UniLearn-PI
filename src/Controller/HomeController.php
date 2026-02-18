<?php

namespace App\Controller;

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
    public function dashboard(): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Redirect all users to instructor dashboard (now accessible by all)
        return $this->redirectToRoute('app_instructor_dashboard');
    }

    #[Route('/become-instructor', name: 'app_become_instructor')]
    public function becomeInstructor(): Response
    {
        return $this->render('become-instructor.html.twig');
    }
}

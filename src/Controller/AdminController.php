<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/users', name: 'app_admin_users')]
    public function users(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = $request->query->get('order', 'asc');

        $users = $userRepository->findBySearchAndSort($search, $sortBy, $sortOrder);
        $adminUser = $this->getUser();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'adminUser' => $adminUser,
        ]);
    }

    #[Route('/users-table', name: 'app_admin_users_table')]
    public function usersTable(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = $request->query->get('order', 'asc');

        $users = $userRepository->findBySearchAndSort($search, $sortBy, $sortOrder);

        return $this->render('admin/_users_table.html.twig', [
            'users' => $users,
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_user_edit')]
    public function editUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('app_admin_dashboard');
        }
        
        return $this->render('admin/user_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
    
    #[Route('/admin/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'User has been deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token. Please try again.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}

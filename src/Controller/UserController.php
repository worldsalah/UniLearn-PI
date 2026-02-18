<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_admin_users_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // Get search term from request
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', '');
        $status = $request->query->get('status', '');

        // Build query
        $queryBuilder = $userRepository->createQueryBuilder('u');

        // Apply search filter
        if ($search) {
            $queryBuilder->where('u.fullName LIKE :search OR u.email LIKE :search')
                      ->setParameter('search', '%'.$search.'%');
        }

        // Apply role filter
        if ($role) {
            $queryBuilder->join('u.role', 'r')
                      ->where('r.name = :role')
                      ->setParameter('role', $role);
        }

        // Apply status filter
        if ($status) {
            $queryBuilder->andWhere('u.status = :status')
                      ->setParameter('status', $status);
        }

        // Order by creation date (newest first)
        $queryBuilder->orderBy('u.createdAt', 'DESC');

        // Get paginated results
        $query = $queryBuilder->getQuery();
        $users = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        // Get statistics
        $totalUsers = $userRepository->count([]);
        $activeUsers = $userRepository->count(['status' => 'active']);
        $inactiveUsers = $userRepository->count(['status' => 'inactive']);

        // Get role statistics
        $roleStats = $userRepository->createQueryBuilder('u')
            ->select('r.name as roleName, COUNT(u.id) as userCount')
            ->join('u.role', 'r')
            ->groupBy('r.name')
            ->getQuery()
            ->getResult();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'roleStats' => $roleStats,
            'currentSearch' => $search,
            'currentRole' => $role,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user, UserRepository $userRepository): Response
    {
        // Get user statistics
        $courseCount = $userRepository->countCoursesByUser($user->getId());
        $quizResultCount = $userRepository->countQuizResultsByUser($user->getId());

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'courseCount' => $courseCount,
            'quizResultCount' => $quizResultCount,
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, EntityManagerInterface $entityManager): Response
    {
        // Toggle user status
        $newStatus = 'active' === $user->getStatus() ? 'inactive' : 'active';
        $user->setStatus($newStatus);

        $entityManager->flush();

        // Add flash message
        $statusText = 'active' === $newStatus ? 'activated' : 'deactivated';
        $this->addFlash('success', "User {$user->getFullName()} has been {$statusText} successfully.");

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $entityManager): Response
    {
        // Store user name for flash message
        $userName = $user->getFullName();

        // Remove user
        $entityManager->remove($user);
        $entityManager->flush();

        // Add flash message
        $this->addFlash('success', "User {$userName} has been deleted successfully.");

        return $this->redirectToRoute('app_admin_users_index');
    }
}

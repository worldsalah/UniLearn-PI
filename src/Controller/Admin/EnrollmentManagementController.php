<?php

namespace App\Controller\Admin;

use App\Entity\Enrollment;
use App\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class EnrollmentManagementController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EnrollmentRepository $enrollmentRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        EnrollmentRepository $enrollmentRepository
    ) {
        $this->entityManager = $entityManager;
        $this->enrollmentRepository = $enrollmentRepository;
    }

    #[Route('/enrollments', name: 'app_admin_enrollments', methods: ['GET'])]
    public function index(): Response
    {
        $enrollments = $this->enrollmentRepository->findAll();
        
        return $this->render('admin/enrollments/index.html.twig', [
            'enrollments' => $enrollments,
        ]);
    }

    #[Route('/enrollments/{id}/delete', name: 'app_admin_enrollment_delete', methods: ['POST'])]
    public function delete(Enrollment $enrollment): Response
    {
        $this->entityManager->remove($enrollment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Enrollment deleted successfully.');

        return $this->redirectToRoute('app_admin_enrollments');
    }

    #[Route('/enrollments/stats', name: 'app_admin_enrollment_stats', methods: ['GET'])]
    public function stats(): Response
    {
        // Get enrollment statistics
        $totalEnrollments = count($this->enrollmentRepository->findAll());
        
        $enrollmentsByMonth = $this->enrollmentRepository->getEnrollmentsByMonth();
        
        $enrollmentsByCourse = $this->enrollmentRepository->getEnrollmentsByCourse();
        
        $recentEnrollments = $this->enrollmentRepository->getRecentEnrollments(30);

        return $this->render('admin/enrollments/stats.html.twig', [
            'totalEnrollments' => $totalEnrollments,
            'enrollmentsByMonth' => $enrollmentsByMonth,
            'enrollmentsByCourse' => $enrollmentsByCourse,
            'recentEnrollments' => $recentEnrollments,
        ]);
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\Student;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/student')]
#[IsGranted('ROLE_ADMIN')]
class StudentController extends AbstractController
{
    #[Route('/', name: 'app_admin_student_index', methods: ['GET'])]
    public function index(StudentRepository $studentRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $studentRepository->createQueryBuilder('s')->getQuery();
        $students = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('admin/student/index.html.twig', [
            'students' => $students,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_student_show', methods: ['GET'])]
    public function show(Student $student): Response
    {
        return $this->render('admin/student/show.html.twig', [
            'student' => $student,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'app_admin_student_delete', methods: ['POST'])]
    public function delete(Request $request, Student $student, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$student->getId(), $request->request->get('_token'))) {
            $entityManager->remove($student);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_student_index', [], Response::HTTP_SEE_OTHER);
    }
}

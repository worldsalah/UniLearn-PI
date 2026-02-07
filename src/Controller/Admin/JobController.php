<?php

namespace App\Controller\Admin;

use App\Entity\Job;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/job')]
// #[IsGranted('ROLE_ADMIN')]
class JobController extends AbstractController
{
    #[Route('/', name: 'app_admin_job_index', methods: ['GET'])]
    public function index(JobRepository $jobRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $jobRepository->createQueryBuilder('j')->orderBy('j.createdAt', 'DESC')->getQuery();
        $jobs = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('admin/job/index.html.twig', [
            'jobs' => $jobs,
        ]);
    }

    #[Route('/new', name: 'app_admin_job_new', methods: ['GET', 'POST'], priority: 2)]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $job = new Job();
        $form = $this->createForm(\App\Form\JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($job);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_job_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/job/new.html.twig', [
            'job' => $job,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_job_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Job $job, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(\App\Form\JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_job_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/job/edit.html.twig', [
            'job' => $job,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_job_delete', methods: ['POST'])]
    public function delete(Request $request, Job $job, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$job->getId(), $request->request->get('_token'))) {
            $entityManager->remove($job);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_job_index', [], Response::HTTP_SEE_OTHER);
    }
}

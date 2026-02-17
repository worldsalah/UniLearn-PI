<?php

namespace App\Controller\Admin;

use App\Entity\Job;
use App\Entity\Application;
use App\Repository\JobRepository;
use App\Repository\ApplicationRepository;
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
    private ApplicationRepository $applicationRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(ApplicationRepository $applicationRepository, EntityManagerInterface $entityManager)
    {
        $this->applicationRepository = $applicationRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_admin_job_index', methods: ['GET'])]
    public function index(JobRepository $jobRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $search = $request->query->get('search', '');
        
        $queryBuilder = $jobRepository->createQueryBuilder('j')
            ->leftJoin('j.applications', 'a')
            ->addSelect('COUNT(a.id) as applicationCount')
            ->groupBy('j.id');
        
        if ($search) {
            $queryBuilder
                ->leftJoin('j.client', 'c')
                ->where('j.title LIKE :search')
                ->orWhere('j.description LIKE :search')
                ->orWhere('c.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        $query = $queryBuilder->orderBy('j.createdAt', 'DESC')->getQuery();
        $jobs = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        // Process jobs to add application count
        $processedJobs = [];
        foreach ($jobs as $job) {
            $jobEntity = $job[0]; // Get the actual Job entity
            $jobEntity->applicationCount = $job['applicationCount'] ?? 0;
            $processedJobs[] = $jobEntity;
        }

        return $this->render('admin/job/index.html.twig', [
            'jobs' => $processedJobs,
            'jobsPaginator' => $jobs, // Keep original paginator for pagination
            'search' => $search,
            'applicationStats' => $this->getApplicationStatistics(),
        ]);
    }

    #[Route('/new', name: 'app_admin_job_new', methods: ['GET', 'POST'], priority: 2)]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $job = new Job();
        
        // Set the client before form creation to avoid validation issues
        $user = $this->getUser();
        if (!$user) {
            // For admin, you might want to set a default user or handle differently
            $job->setClient(null);
        } else {
            $job->setClient($user);
        }
        
        $form = $this->createForm(\App\Form\Form\JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $job->setCreatedAt(new \DateTimeImmutable());
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
        $form = $this->createForm(\App\Form\Form\JobType::class, $job);
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

    #[Route('/{id}', name: 'app_admin_job_show', methods: ['GET'])]
    public function show(Job $job): Response
    {
        return $this->render('admin/job/show.html.twig', [
            'job' => $job,
        ]);
    }

    /**
     * Get application statistics for chart visualization
     */
    private function getApplicationStatistics(): array
    {
        // Get applications per job for the last 30 days
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        $applicationsData = $this->applicationRepository->createQueryBuilder('a')
            ->select('j.title as jobTitle, COUNT(a.id) as applicationCount')
            ->leftJoin('a.job', 'j')
            ->where('a.createdAt >= :date')
            ->andWhere('a.deletedAt IS NULL')
            ->groupBy('j.id', 'j.title')
            ->orderBy('applicationCount', 'DESC')
            ->setParameter('date', $thirtyDaysAgo)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Prepare data for chart
        $jobTitles = [];
        $applicationCounts = [];
        
        foreach ($applicationsData as $data) {
            $jobTitles[] = $data['jobTitle'];
            $applicationCounts[] = $data['applicationCount'];
        }

        // Get total applications
        $totalApplications = $this->applicationRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        // Get applications by status
        $applicationsByStatus = $this->applicationRepository->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->where('a.deletedAt IS NULL')
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();

        $statusData = [
            'pending' => 0,
            'accepted' => 0,
            'rejected' => 0
        ];

        foreach ($applicationsByStatus as $status) {
            $statusData[$status['status']] = $status['count'];
        }

        return [
            'jobTitles' => $jobTitles,
            'applicationCounts' => $applicationCounts,
            'totalApplications' => $totalApplications,
            'applicationsByStatus' => $statusData,
            'topJobs' => array_slice($applicationsData, 0, 5), // Top 5 jobs with most applications
        ];
    }
}

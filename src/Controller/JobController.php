<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Job;
use App\Form\Form\JobType;
use App\Repository\ApplicationRepository;
use App\Service\ApplicationNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/job')]
class JobController extends AbstractController
{
    private ApplicationNotificationService $notificationService;

    public function __construct(ApplicationNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    #[Route('/new', name: 'app_job_new_public', methods: ['GET'])]
    public function newPublic(): Response
    {
        if ($this->getUser() === null) {
            // Redirect to login if not authenticated
            return $this->redirectToRoute('app_login');
        }

        // Redirect to the actual new job form if authenticated
        return $this->redirectToRoute('app_job_new');
    }

    #[Route('/create', name: 'app_job_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $job = new Job();

        // Set the client before form creation to avoid validation issues
        $user = $this->getUser();
        if ($user === null) {
            // Find or create a default user for demo purposes
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'demo@unilearn.com']);
            if ($user === null) {
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

        $job->setClient($user instanceof \App\Entity\User ? $user : null);

        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $job->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($job);
            $entityManager->flush();

            $this->addFlash('success', 'L\'offre d\'emploi a été créée avec succès.');

            return $this->redirectToRoute('app_job_show', ['id' => $job->getId()]);
        }

        // Si le formulaire est soumis mais invalide, afficher les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('job/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_job_show', methods: ['GET'])]
    public function show(Job $job): Response
    {
        return $this->render('job/show.html.twig', [
            'job' => $job,
        ]);
    }

    #[Route('/{id}/apply', name: 'app_job_apply', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function apply(
        Job $job,
        Request $request,
        ApplicationRepository $applicationRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getUser();

        if ($user === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You must be logged in to apply for jobs.',
            ], 401);
        }

        // Check if user has a student profile (freelancer)
        if ($user->getStudent() === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You must have a freelancer profile to apply for jobs.',
            ], 400);
        }

        // Check if already applied
        $userEntity = $user instanceof \App\Entity\User ? $user : null;
        $existingApplication = $userEntity !== null ? $applicationRepository->findByJobAndFreelancer($job, $userEntity) : null;
        if ($existingApplication !== null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You have already applied for this job.',
            ], 400);
        }

        // Check if job is still open
        if ('open' !== $job->getStatus()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'This job is no longer accepting applications.',
            ], 400);
        }

        // Create new application
        $coverLetter = $request->request->get('coverLetter');
        $proposedBudget = $request->request->get('proposedBudget');
        $timeline = $request->request->get('timeline');
        
        $application = new Application();
        $application->setJob($job);
        $application->setFreelancer($userEntity);
        $application->setCoverLetter(is_string($coverLetter) ? $coverLetter : null);
        $application->setProposedBudget(is_string($proposedBudget) ? $proposedBudget : null);
        $application->setTimeline(is_string($timeline) ? $timeline : null);
        $application->setStatus('pending');
        $application->setCreatedAt(new \DateTimeImmutable());

        // Save the application
        $entityManager->persist($application);
        $entityManager->flush();

        // Add application to job
        $job->addApplication($application);
        $entityManager->persist($job);
        $entityManager->flush();

        // Send notifications
        $this->notificationService->notifyClient($application);
        $this->notificationService->confirmApplication($application);

        return new JsonResponse([
            'success' => true,
            'message' => 'Application submitted successfully! The client has been notified.',
            'application_id' => $application->getId(),
        ]);
    }
}

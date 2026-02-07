<?php

namespace App\Controller;

use App\Entity\Job;
use App\Entity\Application;
use App\Repository\ApplicationRepository;
use App\Service\ApplicationNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/job')]
class JobController extends AbstractController
{
    private ApplicationNotificationService $notificationService;

    public function __construct(ApplicationNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You must be logged in to apply for jobs.'
            ], 401);
        }

        // Check if user has a student profile (freelancer)
        if (!$user->getStudent()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You must have a freelancer profile to apply for jobs.'
            ], 400);
        }

        // Check if already applied
        $existingApplication = $applicationRepository->findByJobAndFreelancer($job, $user);
        if ($existingApplication) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You have already applied for this job.'
            ], 400);
        }

        // Check if job is still open
        if ($job->getStatus() !== 'open') {
            return new JsonResponse([
                'success' => false,
                'message' => 'This job is no longer accepting applications.'
            ], 400);
        }

        // Create new application
        $application = new Application();
        $application->setJob($job);
        $application->setFreelancer($user);
        $application->setCoverLetter($request->request->get('coverLetter'));
        $application->setProposedBudget((float) $request->request->get('proposedBudget'));
        $application->setTimeline($request->request->get('timeline'));
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
            'application_id' => $application->getId()
        ]);
    }
}

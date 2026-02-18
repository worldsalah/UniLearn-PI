<?php

namespace App\Service;

use App\Entity\Application;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ApplicationNotificationService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send notification to client about new application.
     */
    public function notifyClient(Application $application): void
    {
        $job = $application->getJob();
        $freelancer = $application->getFreelancer();

        if (null === $job || null === $job->getClient()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@unilearn.com', 'Unilearn Marketplace'))
            ->to(new Address($job->getClient()->getEmail()))
            ->subject('New Application Received: '.($job->getTitle() ?? 'Untitled Job'))
            ->htmlTemplate('emails/new_application.html.twig')
            ->context([
                'job' => $job,
                'application' => $application,
                'freelancer' => $freelancer,
                'client' => $job->getClient(),
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Send confirmation email to freelancer.
     */
    public function confirmApplication(Application $application): void
    {
        $job = $application->getJob();
        $freelancer = $application->getFreelancer();

        if (null === $job || null === $freelancer) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@unilearn.com', 'Unilearn Marketplace'))
            ->to(new Address($freelancer->getEmail()))
            ->subject('Application Confirmed: '.($job->getTitle() ?? 'Untitled Job'))
            ->htmlTemplate('emails/application_confirmation.html.twig')
            ->context([
                'job' => $job,
                'application' => $application,
                'freelancer' => $freelancer,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Send notification when application is accepted.
     */
    public function notifyAccepted(Application $application): void
    {
        $job = $application->getJob();
        $freelancer = $application->getFreelancer();

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@unilearn.com', 'Unilearn Marketplace'))
            ->to(new Address($freelancer->getEmail()))
            ->subject('Congratulations! Your Application Was Accepted')
            ->htmlTemplate('emails/application_accepted.html.twig')
            ->context([
                'job' => $job,
                'application' => $application,
                'freelancer' => $freelancer,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Send notification when application is rejected.
     */
    public function notifyRejected(Application $application): void
    {
        $job = $application->getJob();
        $freelancer = $application->getFreelancer();

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@unilearn.com', 'Unilearn Marketplace'))
            ->to(new Address($freelancer->getEmail()))
            ->subject('Application Update: '.$job->getTitle())
            ->htmlTemplate('emails/application_rejected.html.twig')
            ->context([
                'job' => $job,
                'application' => $application,
                'freelancer' => $freelancer,
            ])
        ;

        $this->mailer->send($email);
    }
}

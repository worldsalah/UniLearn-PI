<?php

namespace App\Service;

use App\Entity\Booking;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BookingNotificationService
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $urlGenerator)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Send meeting invitation email to student when booking is accepted.
     */
    public function sendMeetingInvitation(Booking $booking): void
    {
        $session = $booking->getSession();
        $studentEmail = $booking->getUserEmail();
        
        if ($session === null || $studentEmail === null || $studentEmail === '') {
            return;
        }

        // Generate meeting URL
        $meetingUrl = $this->urlGenerator->generate(
            'booking_meeting',
            ['id' => $booking->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $instructor = $session->getInstructor();
        $instructorName = $instructor !== null 
            ? $instructor->getFullName() 
            : 'Your Instructor';

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@unilearn.com', 'UniLearn Sessions'))
            ->to(new Address($studentEmail, $booking->getFirstName() . ' ' . $booking->getLastName()))
            ->subject('Your Session is Ready! Join Meeting - ' . $session->getName())
            ->htmlTemplate('emails/booking_meeting_invitation.html.twig')
            ->context([
                'booking' => $booking,
                'session' => $session,
                'meetingUrl' => $meetingUrl,
                'instructorName' => $instructorName,
                'studentName' => $booking->getFirstName() . ' ' . $booking->getLastName(),
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Send booking confirmation email to student.
     */
    public function sendBookingConfirmation(Booking $booking): void
    {
        $session = $booking->getSession();
        $studentEmail = $booking->getUserEmail();
        
        if ($session === null || $studentEmail === null || $studentEmail === '') {
            return;
        }

        $instructor = $session->getInstructor();
        $instructorName = $instructor !== null 
            ? $instructor->getFullName() 
            : 'Your Instructor';

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@unilearn.com', 'UniLearn Sessions'))
            ->to(new Address($studentEmail, $booking->getFirstName() . ' ' . $booking->getLastName()))
            ->subject('Booking Confirmed - ' . $session->getName())
            ->htmlTemplate('emails/booking_confirmation.html.twig')
            ->context([
                'booking' => $booking,
                'session' => $session,
                'instructorName' => $instructorName,
                'studentName' => $booking->getFirstName() . ' ' . $booking->getLastName(),
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Send notification to instructor about new booking.
     */
    public function notifyInstructorNewBooking(Booking $booking): void
    {
        $session = $booking->getSession();
        
        if ($session === null) {
            return;
        }

        $instructor = $session->getInstructor();
        if ($instructor === null) {
            return;
        }

        $studentName = $booking->getFirstName() . ' ' . $booking->getLastName();

        $instructorEmail = $instructor->getEmail();
        $instructorName = $instructor->getFullName();
        
        if ($instructorEmail === null || $instructorName === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@unilearn.com', 'UniLearn Sessions'))
            ->to(new Address($instructorEmail, $instructorName))
            ->subject('New Booking Request - ' . $session->getName())
            ->htmlTemplate('emails/instructor_new_booking.html.twig')
            ->context([
                'booking' => $booking,
                'session' => $session,
                'instructorName' => $instructor->getFullName(),
                'studentName' => $studentName,
            ])
        ;

        $this->mailer->send($email);
    }
}

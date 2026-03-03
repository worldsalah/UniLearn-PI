<?php

namespace App\Service\Booking;

use App\Entity\Booking;
use App\Entity\Bundle;
use App\Entity\BundleUsage;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Exception\BookingException;
use App\Exception\BusinessRuleViolationException;
use App\Repository\BookingRepository;
use App\Repository\TimeSlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class BookingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingRepository $bookingRepository,
        private TimeSlotRepository $timeSlotRepository,
        private BookingValidator $validator,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Create a new booking
     */
    public function createBooking(
        User $student,
        User $teacher,
        TimeSlot $timeSlot,
        ?Bundle $bundle = null,
        ?string $notes = null
    ): Booking {
        // Start transaction
        $this->entityManager->beginTransaction();
        
        try {
            // Validate booking can be created
            $this->validator->validateCreation($student, $teacher, $timeSlot, $bundle);
            
            // Lock the time slot to prevent race conditions
            $slotId = $timeSlot->getId();
            if ($slotId === null) {
                throw new BookingException('Time slot ID is missing');
            }
            $lockedSlot = $this->timeSlotRepository->findWithLock($slotId);
            
            if ($lockedSlot === null || !$lockedSlot->isAvailable()) {
                throw new BookingException('Time slot is no longer available');
            }
            
            // Create booking
            $booking = new Booking();
            $booking->setStudent($student);
            $booking->setTeacher($teacher);
            $booking->setTimeSlot($lockedSlot);
            $booking->setNotes($notes);
            
            // Set price from teacher's hourly rate
            $teacherProfile = $teacher->getTeacherProfile();
            $price = $teacherProfile ? $teacherProfile->getHourlyRateFloat() : 0;
            $booking->setPrice((string) $price);
            
            // Handle bundle if provided
            if ($bundle !== null) {
                $this->validator->validateBundle($bundle, $student);
                $booking->setBundle($bundle);
                
                // Deduct from bundle
                $bundle->incrementUsed();
                
                // Create usage record
                $usage = new BundleUsage();
                $usage->setBundle($bundle);
                $usage->setBooking($booking);
                $this->entityManager->persist($usage);
            }
            
            // Mark slot as booked
            $lockedSlot->book();
            
            // Persist booking
            $this->entityManager->persist($booking);
            $this->entityManager->flush();
            $this->entityManager->commit();
            
            // Dispatch event
            // $this->eventDispatcher->dispatch(new BookingCreatedEvent($booking));
            
            return $booking;
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Confirm a booking (teacher action)
     */
    public function confirmBooking(Booking $booking, User $teacher): Booking
    {
        // Validate teacher owns this booking
        $bookingTeacher = $booking->getTeacher();
        if ($bookingTeacher === null || $bookingTeacher->getId() !== $teacher->getId()) {
            throw new BusinessRuleViolationException('Only the assigned teacher can confirm this booking');
        }
        
        // Validate status
        $statusString = $booking->getStatus();
        if ($statusString === null) {
            throw new BusinessRuleViolationException('Booking status is not set');
        }
        
        $status = BookingStatus::tryFrom($statusString);
        if ($status === null || !$status->canConfirm()) {
            throw new BusinessRuleViolationException('Booking cannot be confirmed in its current state');
        }
        
        $booking->confirm();
        $this->entityManager->flush();
        
        // Dispatch event
        // $this->eventDispatcher->dispatch(new BookingConfirmedEvent($booking));
        
        return $booking;
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(Booking $booking, User $cancelledBy, ?string $reason = null): Booking
    {
        // Validate can cancel
        $statusString = $booking->getStatus();
        if ($statusString === null) {
            throw new BusinessRuleViolationException('Booking status is not set');
        }
        
        $status = BookingStatus::tryFrom($statusString);
        if ($status === null || !$status->canCancel()) {
            throw new BusinessRuleViolationException('Booking cannot be cancelled in its current state');
        }
        
        // Check cancellation rules based on who is cancelling
        if ($cancelledBy->getId() === $booking->getStudent()->getId()) {
            // Student cancelling - must be 24h before
            if (!$booking->canStudentCancel()) {
                throw new BusinessRuleViolationException('Students can only cancel bookings more than 24 hours before the session');
            }
        } elseif ($cancelledBy->getId() !== $booking->getTeacher()->getId()) {
            throw new BusinessRuleViolationException('Only the student or teacher can cancel this booking');
        }
        
        $booking->cancel($cancelledBy, $reason);
        $this->entityManager->flush();
        
        // Dispatch event
        // $this->eventDispatcher->dispatch(new BookingCancelledEvent($booking));
        
        return $booking;
    }

    /**
     * Complete a booking (after session ends)
     */
    public function completeBooking(Booking $booking): Booking
    {
        $status = $booking->getStatus();
        if ($status !== BookingStatus::CONFIRMED->value) {
            throw new BusinessRuleViolationException('Only confirmed bookings can be completed');
        }
        
        // Check if session time has passed
        if ($booking->hasSessionPassed() !== true) {
            throw new BusinessRuleViolationException('Cannot complete booking before session end time');
        }
        
        $booking->complete();
        $this->entityManager->flush();
        
        // Dispatch event
        // $this->eventDispatcher->dispatch(new BookingCompletedEvent($booking));
        
        return $booking;
    }

    /**
     * Mark as no-show
     */
    public function markNoShow(Booking $booking): Booking
    {
        $status = $booking->getStatus();
        if ($status !== BookingStatus::CONFIRMED->value) {
            throw new BusinessRuleViolationException('Only confirmed bookings can be marked as no-show');
        }
        
        // Check if session time has passed
        if ($booking->hasSessionPassed() !== true) {
            throw new BusinessRuleViolationException('Cannot mark no-show before session end time');
        }
        
        $booking->markNoShow();
        $this->entityManager->flush();
        
        return $booking;
    }

    /**
     * Auto-complete sessions that have ended
     * Called by cron job
     */
    public function autoCompleteEndedSessions(): int
    {
        $bookings = $this->bookingRepository->findConfirmedButEnded();
        $count = 0;
        
        foreach ($bookings as $booking) {
            try {
                $booking->complete();
                $count++;
            } catch (\Exception $e) {
                // Log error but continue
                error_log("Failed to auto-complete booking {$booking->getId()}: {$e->getMessage()}");
            }
        }
        
        $this->entityManager->flush();
        
        return $count;
    }

    /**
     * Get upcoming bookings for a user
     */
    public function getUpcomingBookings(User $user, int $limit = 10): array
    {
        return $this->bookingRepository->findUpcomingByUser($user, $limit);
    }

    /**
     * Get booking history for a user
     */
    public function getBookingHistory(User $user, int $page = 1, int $limit = 20): array
    {
        return $this->bookingRepository->findHistoryByUser($user, $page, $limit);
    }
}

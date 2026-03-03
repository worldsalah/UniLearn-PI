<?php

namespace App\Service\Booking;

use App\Entity\Booking;
use App\Entity\Bundle;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Enum\UserRole;
use App\Exception\BookingException;
use App\Exception\BusinessRuleViolationException;
use App\Repository\BookingRepository;

class BookingValidator
{
    public function __construct(
        private BookingRepository $bookingRepository
    ) {}

    /**
     * Validate all rules for booking creation
     */
    public function validateCreation(
        User $student,
        User $teacher,
        TimeSlot $timeSlot,
        ?Bundle $bundle = null
    ): void {
        $this->validateStudent($student);
        $this->validateTeacher($teacher);
        $this->validateTimeSlot($timeSlot, $teacher);
        
        if ($bundle !== null) {
            $this->validateBundle($bundle, $student);
        }
        
        $this->validateNoDoubleBooking($student, $timeSlot);
    }

    /**
     * Validate student can make bookings
     */
    public function validateStudent(User $student): void
    {
        if (!$student->hasRole(UserRole::STUDENT)) {
            throw new BusinessRuleViolationException('Only students can make bookings');
        }
    }

    /**
     * Validate teacher can receive bookings
     */
    public function validateTeacher(User $teacher): void
    {
        if (!$teacher->hasRole(UserRole::TEACHER)) {
            throw new BusinessRuleViolationException('User is not a teacher');
        }
        
        $teacherProfile = $teacher->getTeacherProfile();
        if ($teacherProfile === null) {
            throw new BusinessRuleViolationException('Teacher profile not found');
        }
    }

    /**
     * Validate time slot is available
     */
    public function validateTimeSlot(TimeSlot $timeSlot, User $teacher): void
    {
        // Check slot belongs to this teacher
        $slotTeacher = $timeSlot->getAvailability()?->getTeacher()?->getUser();
        if ($slotTeacher === null || $slotTeacher->getId() !== $teacher->getId()) {
            throw new BookingException('Time slot does not belong to this teacher');
        }
        
        // Check slot is available
        if (!$timeSlot->isAvailable()) {
            throw new BookingException('Time slot is not available');
        }
        
        // Check slot is not in the past
        if ($timeSlot->isInPast()) {
            throw new BookingException('Cannot book a time slot in the past');
        }
    }

    /**
     * Validate bundle can be used
     */
    public function validateBundle(Bundle $bundle, User $student): void
    {
        // Check bundle belongs to student
        $bundleStudent = $bundle->getStudent();
        $studentId = $student->getId();
        
        if ($bundleStudent === null || $studentId === null || $bundleStudent->getId() !== $studentId) {
            throw new BusinessRuleViolationException('Bundle does not belong to this student');
        }
        
        // Check bundle can be used
        if (!$bundle->canUse()) {
            $reason = match ($bundle->getStatus()) {
                Bundle::STATUS_EXHAUSTED => 'Bundle has no remaining sessions',
                Bundle::STATUS_EXPIRED => 'Bundle has expired',
                default => 'Bundle cannot be used'
            };
            throw new BusinessRuleViolationException($reason);
        }
    }

    /**
     * Validate student doesn't have double booking
     */
    public function validateNoDoubleBooking(User $student, TimeSlot $timeSlot): void
    {
        $startDateTime = $timeSlot->getStartDateTime();
        $endDateTime = $timeSlot->getEndDateTime();
        
        $conflictingBookings = $this->bookingRepository->findConflictingForStudent(
            $student->getId(),
            $startDateTime,
            $endDateTime
        );
        
        if (!empty($conflictingBookings)) {
            throw new BookingException('You already have a booking during this time');
        }
    }

    /**
     * Validate booking can be cancelled by student
     */
    public function canStudentCancel(Booking $booking): bool
    {
        $statusString = $booking->getStatus();
        if ($statusString === null) {
            return false;
        }
        
        $status = BookingStatus::tryFrom($statusString);
        if ($status === null || !$status->canCancel()) {
            return false;
        }
        
        // Student can only cancel if session is more than 24h away
        return !$booking->getTimeSlot()->isWithinCancellationWindow();
    }

    /**
     * Validate booking can be confirmed by teacher
     */
    public function canTeacherConfirm(Booking $booking, User $teacher): bool
    {
        $statusString = $booking->getStatus();
        if ($statusString === null) {
            return false;
        }
        
        $status = BookingStatus::tryFrom($statusString);
        if ($status === null || !$status->canConfirm()) {
            return false;
        }
        
        $bookingTeacher = $booking->getTeacher();
        $teacherId = $teacher->getId();
        
        return $bookingTeacher !== null && $teacherId !== null && $bookingTeacher->getId() === $teacherId;
    }

    /**
     * Validate booking can transition to new status
     */
    public function canTransition(Booking $booking, BookingStatus $newStatus): bool
    {
        $statusString = $booking->getStatus();
        if ($statusString === null) {
            return false;
        }
        
        $status = BookingStatus::tryFrom($statusString);
        return $status !== null && $status->canTransitionTo($newStatus);
    }
}

<?php

namespace App\Security\Voter;

use App\Entity\Booking;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BookingVoter extends Voter
{
    public const VIEW = 'BOOKING_VIEW';
    public const CREATE = 'BOOKING_CREATE';
    public const CONFIRM = 'BOOKING_CONFIRM';
    public const CANCEL = 'BOOKING_CANCEL';
    public const COMPLETE = 'BOOKING_COMPLETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::CREATE, self::CONFIRM, self::CANCEL, self::COMPLETE], true)) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return true; // No subject needed for create
        }

        return $subject instanceof Booking;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::CREATE => $this->canCreate($user),
            self::CONFIRM => $this->canConfirm($subject, $user),
            self::CANCEL => $this->canCancel($subject, $user),
            self::COMPLETE => $this->canComplete($subject, $user),
            default => false,
        };
    }

    /**
     * User can view booking if they are student, teacher, or admin
     */
    private function canView(Booking $booking, User $user): bool
    {
        if ($user->hasRole(UserRole::ADMIN)) {
            return true;
        }

        return $booking->getStudent()->getId() === $user->getId()
            || $booking->getTeacher()->getId() === $user->getId();
    }

    /**
     * Only students can create bookings
     */
    private function canCreate(User $user): bool
    {
        return $user->hasRole(UserRole::STUDENT);
    }

    /**
     * Only the assigned teacher can confirm
     */
    private function canConfirm(Booking $booking, User $user): bool
    {
        if (!$user->hasRole(UserRole::TEACHER)) {
            return false;
        }

        $statusString = $booking->getStatus();
        if ($statusString === null) {
            return false;
        }
        
        try {
            $status = BookingStatus::from($statusString);
        } catch (\ValueError $e) {
            return false;
        }

        return $booking->getTeacher()->getId() === $user->getId()
            && $status->canConfirm();
    }

    /**
     * Student or teacher can cancel (with restrictions)
     */
    private function canCancel(Booking $booking, User $user): bool
    {
        $statusString = $booking->getStatus();
        if ($statusString === null) {
            return false;
        }
        
        try {
            $status = BookingStatus::from($statusString);
        } catch (\ValueError $e) {
            return false;
        }
        
        if (!$status->canCancel()) {
            return false;
        }

        // Admin can always cancel
        if ($user->hasRole(UserRole::ADMIN)) {
            return true;
        }

        // Teacher can cancel their own bookings
        $bookingTeacher = $booking->getTeacher();
        if ($user->hasRole(UserRole::TEACHER) && $bookingTeacher !== null && $bookingTeacher->getId() === $user->getId()) {
            return true;
        }

        // Student can cancel if more than 24h before session
        $bookingStudent = $booking->getStudent();
        if ($user->hasRole(UserRole::STUDENT) && $bookingStudent !== null && $bookingStudent->getId() === $user->getId()) {
            return $booking->canStudentCancel();
        }

        return false;
    }

    /**
     * Only admin or system can mark as complete
     */
    private function canComplete(Booking $booking, User $user): bool
    {
        if (!$booking->hasSessionPassed()) {
            return false;
        }

        return $user->hasRole(UserRole::ADMIN);
    }
}

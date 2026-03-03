<?php

namespace App\Enum;

/**
 * Booking status lifecycle states
 * 
 * State transitions:
 * PENDING → CONFIRMED (teacher confirms)
 * PENDING → CANCELLED (student/teacher cancels)
 * PENDING → NO_SHOW (student doesn't show up)
 * CONFIRMED → COMPLETED (session ends successfully)
 * CONFIRMED → CANCELLED (cancelled before session)
 * CONFIRMED → NO_SHOW (student doesn't show)
 */
enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';

    /**
     * Get all statuses as array
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Confirmation',
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
            self::NO_SHOW => 'No Show',
        };
    }

    /**
     * Get CSS class for status badge
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::COMPLETED => 'primary',
            self::NO_SHOW => 'secondary',
        };
    }

    /**
     * Check if booking can be confirmed
     */
    public function canConfirm(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if booking can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    /**
     * Check if booking is active (not ended)
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    /**
     * Check if booking has ended
     */
    public function hasEnded(): bool
    {
        return in_array($this, [self::CANCELLED, self::COMPLETED, self::NO_SHOW], true);
    }

    /**
     * Get valid transitions from current state
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::CONFIRMED, self::CANCELLED, self::NO_SHOW],
            self::CONFIRMED => [self::COMPLETED, self::CANCELLED, self::NO_SHOW],
            self::CANCELLED, self::COMPLETED, self::NO_SHOW => [],
        };
    }

    /**
     * Check if transition to new status is valid
     */
    public function canTransitionTo(BookingStatus $newStatus): bool
    {
        return in_array($newStatus, $this->validTransitions(), true);
    }
}

<?php

namespace App\Enum;

/**
 * Bundle types for session packages
 */
enum BundleType: string
{
    case SINGLE = 'single';
    case PACK_5 = 'pack_5';
    case PACK_10 = 'pack_10';

    /**
     * Get number of sessions for each bundle type
     */
    public function sessions(): int
    {
        return match ($this) {
            self::SINGLE => 1,
            self::PACK_5 => 5,
            self::PACK_10 => 10,
        };
    }

    /**
     * Get discount percentage (0-1)
     */
    public function discount(): float
    {
        return match ($this) {
            self::SINGLE => 0.00,
            self::PACK_5 => 0.10,   // 10% discount
            self::PACK_10 => 0.20,  // 20% discount
        };
    }

    /**
     * Get label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Single Session',
            self::PACK_5 => '5-Session Pack (10% off)',
            self::PACK_10 => '10-Session Pack (20% off)',
        };
    }

    /**
     * Calculate price with discount
     */
    public function calculatePrice(float $hourlyRate): float
    {
        $basePrice = $hourlyRate * $this->sessions();
        return round($basePrice * (1 - $this->discount()), 2);
    }

    /**
     * Get savings amount
     */
    public function savings(float $hourlyRate): float
    {
        $basePrice = $hourlyRate * $this->sessions();
        $discountedPrice = $this->calculatePrice($hourlyRate);
        return round($basePrice - $discountedPrice, 2);
    }

    /**
     * Get expiration period in months
     */
    public function expirationMonths(): int
    {
        return match ($this) {
            self::SINGLE => 2,
            self::PACK_5 => 4,
            self::PACK_10 => 6,
        };
    }
}

<?php

namespace App\Enum;

/**
 * User roles for the tutoring platform
 */
enum UserRole: string
{
    case STUDENT = 'ROLE_STUDENT';
    case INSTRUCTOR = 'ROLE_INSTRUCTOR';
    case ADMIN = 'ROLE_ADMIN';

    /**
     * Get all roles as array
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get role label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Student',
            self::INSTRUCTOR => 'Instructor',
            self::ADMIN => 'Administrator',
        };
    }

    /**
     * Check if this role can book sessions
     */
    public function canBook(): bool
    {
        return $this === self::STUDENT;
    }

    /**
     * Check if this role can teach
     */
    public function canTeach(): bool
    {
        return $this === self::INSTRUCTOR;
    }

    /**
     * Check if this role has admin access
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}

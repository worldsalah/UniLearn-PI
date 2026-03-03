<?php

namespace App\Exception;

class BookingException extends \RuntimeException
{
    public const SLOT_NOT_AVAILABLE = 'slot_not_available';
    public const DOUBLE_BOOKING = 'double_booking';
    public const INVALID_TIME = 'invalid_time';

    private ?string $errorCode;

    public function __construct(string $message, ?string $errorCode = null, int $httpCode = 400)
    {
        parent::__construct($message, $httpCode);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}

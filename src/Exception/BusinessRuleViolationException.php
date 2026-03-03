<?php

namespace App\Exception;

class BusinessRuleViolationException extends \RuntimeException
{
    public const NOT_ENOUGH_SESSIONS = 'not_enough_sessions';
    public const BUNDLE_EXPIRED = 'bundle_expired';
    public const CANCELLATION_TOO_LATE = 'cancellation_too_late';
    public const NOT_AUTHORIZED = 'not_authorized';
    public const INVALID_STATUS_TRANSITION = 'invalid_status_transition';

    private ?string $rule;

    public function __construct(string $message, ?string $rule = null)
    {
        parent::__construct($message);
        $this->rule = $rule;
    }

    public function getRule(): ?string
    {
        return $this->rule;
    }
}

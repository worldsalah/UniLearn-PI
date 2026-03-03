<?php

namespace App\Exception;

class BundleException extends \RuntimeException
{
    public const NO_SESSIONS_REMAINING = 'no_sessions_remaining';
    public const BUNDLE_EXPIRED = 'bundle_expired';
    public const BUNDLE_EXHAUSTED = 'bundle_exhausted';
    public const NOT_OWNER = 'not_owner';

    private ?string $errorCode;

    public function __construct(string $message, ?string $errorCode = null)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}

<?php

namespace App\Service\Security;

class RateLimitException extends \RuntimeException
{
    public function __construct(
        string $message,
        private int $limit,
        private int $retryAfter
    ) {
        parent::__construct($message, 429);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

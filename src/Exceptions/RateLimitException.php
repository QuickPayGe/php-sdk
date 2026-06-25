<?php

declare(strict_types=1);

namespace Quickpay\Exceptions;

class RateLimitException extends QuickpayException
{
    public function __construct(
        string $message,
        string $errorCode,
        int $httpStatus,
        private readonly int $retryAfterSeconds = 60,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $errorCode, $httpStatus, $previous);
    }

    public function retryAfter(): int
    {
        return $this->retryAfterSeconds;
    }
}

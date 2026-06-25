<?php

declare(strict_types=1);

namespace Quickpay\Exceptions;

class ValidationException extends QuickpayException
{
    public function __construct(
        string $message,
        string $errorCode,
        int $httpStatus,
        public readonly array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $errorCode, $httpStatus, $previous);
    }
}

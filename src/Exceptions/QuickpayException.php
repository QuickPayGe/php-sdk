<?php

declare(strict_types=1);

namespace Quickpay\Exceptions;

class QuickpayException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $httpStatus,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }
}

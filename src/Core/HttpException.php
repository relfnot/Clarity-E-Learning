<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    private string $errorCode;
    private int $statusCode;
    /** @var array<string, mixed> */
    private array $details;
    /** @var array<string, string> */
    private array $headers;

    /**
     * @param array<string, mixed> $details
     * @param array<string, string> $headers
     */
    public function __construct(
        string $errorCode,
        string $message,
        int $statusCode = 400,
        array $details = [],
        array $headers = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->details = $details;
        $this->headers = $headers;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}

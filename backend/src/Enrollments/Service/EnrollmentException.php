<?php

declare(strict_types=1);

namespace App\Enrollments\Service;

use RuntimeException;

final class EnrollmentException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

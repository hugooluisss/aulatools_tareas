<?php

declare(strict_types=1);

namespace App\Subjects\Service;

use RuntimeException;

final class SubjectException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

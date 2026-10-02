<?php

declare(strict_types=1);

namespace App\Tasks\Service;

use RuntimeException;

final class TaskException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

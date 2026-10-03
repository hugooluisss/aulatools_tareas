<?php

declare(strict_types=1);

namespace App\Groups\Service;

use RuntimeException;

final class GroupException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

<?php

declare(strict_types=1);

namespace App\Users\Service;

use RuntimeException;

final class UserException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

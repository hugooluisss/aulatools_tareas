<?php

declare(strict_types=1);

namespace App\Cycles\Service;

use RuntimeException;

final class CycleException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

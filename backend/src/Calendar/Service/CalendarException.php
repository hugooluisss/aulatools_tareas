<?php

declare(strict_types=1);

namespace App\Calendar\Service;

use RuntimeException;

final class CalendarException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

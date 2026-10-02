<?php

declare(strict_types=1);

namespace App\Announcements\Service;

use RuntimeException;

final class AnnouncementException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

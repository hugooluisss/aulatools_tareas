<?php

declare(strict_types=1);

namespace App\TaskComments\Service;

use RuntimeException;

final class CommentException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

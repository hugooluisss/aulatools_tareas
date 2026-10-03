<?php

declare(strict_types=1);

namespace App\StudyPlans\Service;

use RuntimeException;

final class StudyPlanException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

<?php

declare(strict_types=1);

namespace App\StudentNotes\Service;

final class StudentNoteException extends \RuntimeException
{
    public function __construct(string $message, public int $status)
    {
        parent::__construct($message);
    }
}

<?php

declare(strict_types=1);

namespace App\Shared\Mail;

final readonly class EmailMessage
{
    public function __construct(
        public string $to,
        public string $subject,
        public string $textBody,
        public ?string $htmlBody = null,
    ) {
    }
}

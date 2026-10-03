<?php

declare(strict_types=1);

namespace App\Shared\Mail;

interface EmailSender
{
    /** @throws MailException */
    public function send(EmailMessage $message): void;
}

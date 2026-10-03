<?php

declare(strict_types=1);

use App\Shared\Mail\EmailSender;
use App\Shared\Mail\SymfonyEmailSender;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

return [
    MailerInterface::class => static fn () => new Mailer(Transport::fromDsn(getenv('MAILER_DSN') ?: 'smtp://mailpit:1025')),
    EmailSender::class => SymfonyEmailSender::class,
];

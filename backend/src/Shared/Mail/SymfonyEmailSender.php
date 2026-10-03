<?php

declare(strict_types=1);

namespace App\Shared\Mail;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Throwable;

final class SymfonyEmailSender implements EmailSender
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function send(EmailMessage $message): void
    {
        $email = (new Email())
            ->from(getenv('MAIL_FROM') ?: 'no-reply@aulatools.local')
            ->to($message->to)
            ->subject($message->subject)
            ->text($message->textBody);
        if ($message->htmlBody !== null) {
            $email->html($message->htmlBody);
        }

        try {
            $this->mailer->send($email);
        } catch (Throwable $exception) {
            throw new MailException('Email delivery failed.', 0, $exception);
        }
    }
}

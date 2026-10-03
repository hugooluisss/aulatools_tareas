<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Shared\Mail\EmailMessage;
use App\Shared\Mail\EmailSender;

final class PasswordResetMailer
{
    public function __construct(private EmailSender $emailSender)
    {
    }

    public function send(string $recipient, string $token): void
    {
        $url = rtrim(getenv('APP_URL') ?: 'http://localhost:4330/aulatools-tareas_frontend', '/') . '/reset-password?token=' . rawurlencode($token);
        $this->emailSender->send(new EmailMessage(
            $recipient,
            'Restablecer contraseña',
            "Usa este enlace para restablecer tu contraseña. Vence en 1 hora: {$url}",
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests;

use App\Shared\Mail\EmailMessage;
use App\Shared\Mail\SymfonyEmailSender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class SymfonyEmailSenderTest extends TestCase
{
    public function testMapsMessageToSymfonyEmailWithoutNetwork(): void
    {
        $dispatcher = new EventDispatcher();
        $event = null;
        $dispatcher->addListener(MessageEvent::class, static function (MessageEvent $messageEvent) use (&$event): void {
            $event = $messageEvent;
        });
        $transport = new NullTransport($dispatcher);
        $sender = new SymfonyEmailSender(new Mailer($transport));
        $message = new EmailMessage('person@example.com', 'Subject', 'Plain body', '<p>HTML body</p>');

        $sender->send($message);
        self::assertInstanceOf(MessageEvent::class, $event);
        self::assertInstanceOf(Email::class, $event->getMessage());
        self::assertSame('person@example.com', $event->getEnvelope()->getRecipients()[0]->toString());
        self::assertSame('Subject', $event->getMessage()->getSubject());
        self::assertSame('Plain body', $event->getMessage()->getTextBody());
        self::assertSame('<p>HTML body</p>', $event->getMessage()->getHtmlBody());
    }
}

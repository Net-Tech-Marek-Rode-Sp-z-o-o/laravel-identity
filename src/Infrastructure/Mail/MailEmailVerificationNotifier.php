<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Mail;

use DateTimeImmutable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use NetCode\Identity\Application\Ports\EmailVerificationNotifier;
use NetCode\Identity\Domain\ValueObjects\Email;

final readonly class MailEmailVerificationNotifier implements EmailVerificationNotifier
{
    public function __construct(
        private Mailer $mailer,
    ) {}

    public function notify(Email $email, string $token, DateTimeImmutable $expiresAt): void
    {
        $this->mailer->raw(
            text: 'Your e-mail verification token: '.$token,
            callback: fn (Message $message): Message => $message->to(address: $email->value())->subject(subject: 'Verify your e-mail'),
        );
    }
}

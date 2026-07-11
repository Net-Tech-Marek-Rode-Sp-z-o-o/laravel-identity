<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Mail;

use DateTimeImmutable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use NetCode\Identity\Application\Port\PasswordResetNotifier;
use NetCode\Identity\Domain\ValueObjects\Email;

final readonly class MailPasswordResetNotifier implements PasswordResetNotifier
{
    public function __construct(
        private Mailer $mailer,
    ) {}

    public function notify(Email $email, string $token, DateTimeImmutable $expiresAt): void
    {
        $this->mailer->raw(
            'Your password reset token: '.$token,
            function (Message $message) use ($email): void {
                $message->to($email->value())->subject('Password reset');
            },
        );
    }
}

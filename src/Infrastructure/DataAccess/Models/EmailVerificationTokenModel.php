<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use NetCode\Domain\Laravel\IdentifierCast;
use NetCode\Identity\Domain\ValueObjects\EmailVerificationTokenId;
use NetCode\Identity\Domain\ValueObjects\UserId;

/**
 * @property EmailVerificationTokenId $id
 * @property UserId $user_id
 * @property string $token_hash
 * @property DateTimeImmutable $expires_at
 * @property DateTimeImmutable|null $used_at
 */
final class EmailVerificationTokenModel extends Model
{
    protected $table = 'identity_email_verification_tokens';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'id' => IdentifierCast::class.':'.EmailVerificationTokenId::class,
        'user_id' => IdentifierCast::class.':'.UserId::class,
        'expires_at' => 'immutable_datetime',
        'used_at' => 'immutable_datetime',
    ];
}

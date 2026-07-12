<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use NetCode\Domain\Laravel\IdentifierCast;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Identity\Domain\ValueObjects\RealmId;

/**
 * @property InvitationId $id
 * @property RealmId|null $realm_id
 * @property string $email
 * @property string $token_hash
 * @property array<string, mixed> $metadata
 * @property DateTimeImmutable $expires_at
 * @property DateTimeImmutable|null $accepted_at
 * @property DateTimeImmutable|null $revoked_at
 */
final class InvitationModel extends Model
{
    protected $table = 'identity_invitations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'id' => IdentifierCast::class.':'.InvitationId::class,
        'realm_id' => IdentifierCast::class.':'.RealmId::class,
        'metadata' => 'array',
        'expires_at' => 'immutable_datetime',
        'accepted_at' => 'immutable_datetime',
        'revoked_at' => 'immutable_datetime',
    ];
}

<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use NetCode\Domain\Laravel\IdentifierCast;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\LinkedAccountId;
use NetCode\Identity\Domain\ValueObjects\UserId;

/**
 * @property LinkedAccountId $id
 * @property UserId $user_id
 * @property SocialProvider $provider
 * @property string $provider_id
 * @property DateTimeImmutable $created_at
 */
final class LinkedAccountModel extends Model
{
    protected $table = 'identity_linked_accounts';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'id' => IdentifierCast::class.':'.LinkedAccountId::class,
        'user_id' => IdentifierCast::class.':'.UserId::class,
        'provider' => SocialProvider::class,
        'created_at' => 'immutable_datetime',
    ];
}

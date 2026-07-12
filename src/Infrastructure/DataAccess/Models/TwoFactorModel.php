<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $user_id
 * @property string $secret
 * @property DateTimeImmutable|null $confirmed_at
 * @property list<string> $recovery_codes
 */
final class TwoFactorModel extends Model
{
    protected $table = 'identity_user_two_factor';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'confirmed_at' => 'immutable_datetime',
        'recovery_codes' => 'array',
    ];
}

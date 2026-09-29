<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Models;

use DateTimeImmutable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use NetCode\Domain\Laravel\IdentifierCast;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\UserId;

/**
 * @property UserId $id
 * @property RealmId|null $realm_id
 * @property string $email
 * @property string $name
 * @property string|null $password_hash
 * @property DateTimeImmutable|null $deleted_at
 * @property DateTimeImmutable|null $email_verified_at
 * @property TwoFactorModel|null $twoFactor
 */
final class UserModel extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasApiTokens;
    use SoftDeletes;

    /** @var list<string> */
    public const array BASE_WITH = ['twoFactor'];

    protected $table = 'identity_users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'id' => IdentifierCast::class.':'.UserId::class,
        'realm_id' => IdentifierCast::class.':'.RealmId::class,
        'deleted_at' => 'immutable_datetime',
        'email_verified_at' => 'immutable_datetime',
    ];

    /** @return HasOne<TwoFactorModel, $this> */
    public function twoFactor(): HasOne
    {
        return $this->hasOne(TwoFactorModel::class, 'user_id');
    }
}

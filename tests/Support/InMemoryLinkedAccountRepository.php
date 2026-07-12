<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Domain\Contracts\LinkedAccountRepository;
use NetCode\Identity\Domain\LinkedAccount;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\LinkedAccountId;

final class InMemoryLinkedAccountRepository implements LinkedAccountRepository
{
    /** @var array<string, LinkedAccount> */
    private array $accounts = [];

    public function nextId(): LinkedAccountId
    {
        return LinkedAccountId::random();
    }

    public function findByProvider(SocialProvider $provider, string $providerId): LinkedAccount|null
    {
        foreach ($this->accounts as $account) {
            if ($account->provider() === $provider && $account->providerId() === $providerId) {
                return $account;
            }
        }

        return null;
    }

    public function save(LinkedAccount $account): void
    {
        $this->accounts[$account->id()->value()] = $account;
    }

    public function count(): int
    {
        return count($this->accounts);
    }
}

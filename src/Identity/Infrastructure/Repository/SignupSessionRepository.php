<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Illuminate\Support\Facades\Redis;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SignupSession;

class SignupSessionRepository implements SignupSessionRepositoryInterface
{
    private const string KEY_PREFIX = 'signup_session:';

    public function store(OAuthState $state, SignupSession $session): void
    {
        $key = $this->buildKey($state);
        $ttl = $state->expiresAt()->getTimestamp() - time();

        if ($ttl > 0) {
            Redis::setex($key, $ttl, $session->accountType()->value);
        }
    }

    public function find(OAuthState $state): ?SignupSession
    {
        $key = $this->buildKey($state);
        $value = Redis::get($key);

        if ($value === null || $value === false) {
            return null;
        }

        $accountType = AccountType::tryFrom($value);
        if ($accountType === null) {
            return null;
        }

        return new SignupSession($accountType);
    }

    public function delete(OAuthState $state): void
    {
        $key = $this->buildKey($state);
        Redis::del($key);
    }

    private function buildKey(OAuthState $state): string
    {
        return self::KEY_PREFIX . $state;
    }
}

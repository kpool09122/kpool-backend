<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Illuminate\Support\Facades\Redis;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
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
            $data = [
                'account_type' => $session->accountType()?->value,
                'invitation_token' => $session->invitationToken() !== null
                    ? (string) $session->invitationToken()
                    : null,
            ];
            Redis::setex($key, $ttl, json_encode($data));
        }
    }

    public function find(OAuthState $state): ?SignupSession
    {
        $key = $this->buildKey($state);
        $value = Redis::get($key);

        if ($value === null || $value === false) {
            return null;
        }

        $data = json_decode($value, true);

        if (! is_array($data)) {
            return null;
        }

        $accountType = isset($data['account_type'])
            ? AccountType::tryFrom($data['account_type'])
            : null;

        $invitationToken = isset($data['invitation_token'])
            ? new InvitationToken($data['invitation_token'])
            : null;

        return new SignupSession($accountType, $invitationToken);
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

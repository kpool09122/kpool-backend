<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

class SignupSessionRepository implements SignupSessionRepositoryInterface
{
    private const string KEY_PREFIX = 'signup_session:';

    public function store(OAuthState $state, SignupSession $session, DateTimeImmutable $expiresAt): void
    {
        $key = $this->buildKey($state);
        $ttl = $expiresAt->getTimestamp() - time();

        if ($ttl > 0) {
            $data = [
                'account_type' => $session->accountType()?->value,
                'registration_country' => $session->registrationCountry()?->value,
                'registration_region' => $session->registrationRegion() !== null ? (string) $session->registrationRegion() : null,
                'one_time_token' => $session->oneTimeToken() !== null
                    ? (string) $session->oneTimeToken()
                    : null,
                'return_to' => $session->returnTo(),
            ];
            Redis::setex($key, $ttl, json_encode($data));
        }
    }

    public function find(OAuthState $state): ?SignupSession
    {
        $key = $this->buildKey($state);
        $value = Redis::get($key);

        if (! is_string($value)) {
            return null;
        }

        $data = json_decode($value, true);

        if (! is_array($data)) {
            return null;
        }

        $accountType = isset($data['account_type'])
            ? AccountType::tryFrom(TypedValue::string($data['account_type']))
            : null;

        $oneTimeToken = isset($data['one_time_token'])
            ? new OneTimeToken(TypedValue::string($data['one_time_token']))
            : null;

        $returnTo = isset($data['return_to']) ? TypedValue::string($data['return_to']) : null;

        return new SignupSession($accountType, $oneTimeToken, $returnTo, isset($data['registration_country']) ? CountryCode::tryFrom(TypedValue::string($data['registration_country'])) : null, isset($data['registration_region'], $data['registration_country']) ? new SubdivisionCode(CountryCode::from(TypedValue::string($data['registration_country'])), TypedValue::string($data['registration_region'])) : null);
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

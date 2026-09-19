<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Illuminate\Support\Facades\Redis;
use Source\Identity\Domain\Entity\PasskeyChallengeSession;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Repository\PasskeyChallengeSessionRepositoryInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PasskeyChallengeSessionRepository implements PasskeyChallengeSessionRepositoryInterface
{
    private const string KEY_PREFIX = 'passkey_challenge:';
    private const int TTL_SECONDS = 300;

    public function save(PasskeyChallengeSession $session): void
    {
        $data = json_encode([
            'purpose' => $session->purpose(),
            'options' => $session->optionsJson(),
            'identity_identifier' => $session->identityIdentifier() === null ? null : (string) $session->identityIdentifier(),
            'signup_data' => $session->signupData(),
        ], JSON_THROW_ON_ERROR);
        Redis::setex(self::KEY_PREFIX . $session->identifier(), self::TTL_SECONDS, $data);
    }

    public function consume(string $identifier, string $purpose): PasskeyChallengeSession
    {
        $value = Redis::command('getdel', [self::KEY_PREFIX . $identifier]);
        if (! is_string($value)) {
            throw new ChallengeSessionNotFoundException('チャレンジが存在しないか期限切れです');
        }
        /** @var array{purpose: string, options: string, identity_identifier: ?string, signup_data: ?array<string, mixed>} $data */
        $data = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        if ($data['purpose'] !== $purpose) {
            throw new ChallengeSessionNotFoundException('チャレンジの用途が一致しません');
        }

        return new PasskeyChallengeSession(
            $identifier,
            $data['purpose'],
            $data['options'],
            $data['identity_identifier'] === null ? null : new IdentityIdentifier($data['identity_identifier']),
            $data['signup_data'],
        );
    }
}

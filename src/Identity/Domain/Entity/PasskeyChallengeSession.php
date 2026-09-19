<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use Source\Identity\Domain\Exception\InvalidPasskeyException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PasskeyChallengeSession
{
    public const PURPOSE_SIGNUP = 'signup';
    public const PURPOSE_LOGIN = 'login';
    public const PURPOSE_ADD = 'add';

    /** @param array<string, mixed>|null $signupData */
    public function __construct(
        private string $identifier,
        private string $purpose,
        private string $optionsJson,
        private ?IdentityIdentifier $identityIdentifier = null,
        private ?array $signupData = null,
    ) {
        if ($identifier === '' || $optionsJson === '') {
            throw new InvalidPasskeyException('チャレンジセッションが不正です');
        }
        if (! in_array($purpose, [self::PURPOSE_SIGNUP, self::PURPOSE_LOGIN, self::PURPOSE_ADD], true)) {
            throw new InvalidPasskeyException('チャレンジ用途が不正です');
        }
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function purpose(): string
    {
        return $this->purpose;
    }

    public function optionsJson(): string
    {
        return $this->optionsJson;
    }

    public function identityIdentifier(): ?IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    /** @return array<string, mixed>|null */
    public function signupData(): ?array
    {
        return $this->signupData;
    }
}

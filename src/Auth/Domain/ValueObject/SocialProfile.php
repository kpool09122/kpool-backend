<?php

declare(strict_types=1);

namespace Source\Auth\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Email;

readonly class SocialProfile
{
    public function __construct(
        private SocialProvider $provider,
        private string $providerUserId,
        private Email $email,
        private ?string $name = null,
        private ?string $avatarUrl = null,
    ) {
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function providerUserId(): string
    {
        return $this->providerUserId;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatarUrl;
    }
}

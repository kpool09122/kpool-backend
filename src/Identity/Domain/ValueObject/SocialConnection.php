<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;

readonly class SocialConnection
{
    public function __construct(
        private SocialProvider $provider,
        private string $providerUserId,
    ) {
        $this->validate($providerUserId);
    }

    private function validate(string $providerUserId): void
    {
        if (empty($providerUserId)) {
            throw new InvalidArgumentException('Provider user id is required');
        }
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function providerUserId(): string
    {
        return $this->providerUserId;
    }

    public function equals(self $other): bool
    {
        return $this->provider === $other->provider && $this->providerUserId === $other->providerUserId;
    }
}

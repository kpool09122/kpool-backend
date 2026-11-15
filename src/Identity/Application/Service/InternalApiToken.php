<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use DateTimeImmutable;
use SensitiveParameter;

/** Transport credential: never serialize into a business response or log. */
readonly class InternalApiToken
{
    public function __construct(
        #[SensitiveParameter]
        private string $token,
        public DateTimeImmutable $expiresAt,
    ) {
    }

    public function reveal(): string
    {
        return $this->token;
    }

    /** @return array{token: string, expiresAt: DateTimeImmutable} */
    public function __debugInfo(): array
    {
        return ['token' => '[REDACTED]', 'expiresAt' => $this->expiresAt];
    }
}

<?php

declare(strict_types=1);

namespace Application\Http\RateLimit;

readonly class RateLimitSubject
{
    private function __construct(
        public string $type,
        public string $identifier,
    ) {
    }

    public static function account(string $identifier): self
    {
        return new self('account', $identifier);
    }

    public static function ip(string $identifier): self
    {
        return new self('ip', hash('sha256', $identifier));
    }
}

<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class AuthenticationMethodsReadModel
{
    /** @param string[] $linkedSocialProviders */
    public function __construct(
        private int $passkeyCount,
        private array $linkedSocialProviders,
    ) {
    }

    public function passkeyCount(): int
    {
        return $this->passkeyCount;
    }

    /** @return string[] */
    public function linkedSocialProviders(): array
    {
        return $this->linkedSocialProviders;
    }

    /** @return array{passkeyCount: int, linkedSocialProviders: string[]} */
    public function toArray(): array
    {
        return [
            'passkeyCount' => $this->passkeyCount,
            'linkedSocialProviders' => $this->linkedSocialProviders,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Query;

readonly class PrincipalReadModel
{
    public function __construct(
        private string $principalIdentifier,
        private string $identityIdentifier,
        private bool $isDelegatedPrincipal,
        private bool $isEnabled,
    ) {
    }

    /**
     * @return array{principalIdentifier: string, identityIdentifier: string, isDelegatedPrincipal: bool, isEnabled: bool}
     */
    public function toArray(): array
    {
        return [
            'principalIdentifier' => $this->principalIdentifier,
            'identityIdentifier' => $this->identityIdentifier,
            'isDelegatedPrincipal' => $this->isDelegatedPrincipal,
            'isEnabled' => $this->isEnabled,
        ];
    }
}

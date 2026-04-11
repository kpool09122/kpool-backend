<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Wiki\Principal\Domain\Entity\Principal;

class CreatePrincipalOutput implements CreatePrincipalOutputPort
{
    private ?Principal $principal = null;

    public function setPrincipal(Principal $principal): void
    {
        $this->principal = $principal;
    }

    /**
     * @return array{principalIdentifier: ?string, identityIdentifier: ?string, isDelegatedPrincipal: ?bool, isEnabled: ?bool}
     */
    public function toArray(): array
    {
        if ($this->principal === null) {
            return [
                'principalIdentifier' => null,
                'identityIdentifier' => null,
                'isDelegatedPrincipal' => null,
                'isEnabled' => null,
            ];
        }

        return [
            'principalIdentifier' => (string) $this->principal->principalIdentifier(),
            'identityIdentifier' => (string) $this->principal->identityIdentifier(),
            'isDelegatedPrincipal' => $this->principal->isDelegatedPrincipal(),
            'isEnabled' => $this->principal->isEnabled(),
        ];
    }
}

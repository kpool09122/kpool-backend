<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Analyze\Principal\Domain\Entity\Principal;

class CreatePrincipalOutput implements CreatePrincipalOutputPort
{
    private ?Principal $principal = null;

    public function setPrincipal(Principal $principal): void
    {
        $this->principal = $principal;
    }

    public function principal(): ?Principal
    {
        return $this->principal;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->principal === null) {
            return [];
        }
        $principal = $this->principal;

        return [
            'principalIdentifier' => (string) $principal->principalIdentifier(),
            'identityIdentifier' => (string) $principal->identityIdentifier(),
        ];
    }
}

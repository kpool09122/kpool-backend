<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use Source\SiteManagement\Principal\Domain\Entity\Principal;

class ProvisionPrincipalOutput implements ProvisionPrincipalOutputPort
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

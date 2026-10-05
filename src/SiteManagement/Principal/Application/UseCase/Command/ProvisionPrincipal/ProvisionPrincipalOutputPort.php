<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use Source\SiteManagement\Principal\Domain\Entity\Principal;

interface ProvisionPrincipalOutputPort
{
    public function setPrincipal(Principal $principal): void;

    public function principal(): ?Principal;

    /** @return array<string, mixed> */
    public function toArray(): array;
}

<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Wiki\Principal\Domain\Entity\Principal;

interface CreatePrincipalOutputPort
{
    public function setPrincipal(Principal $principal): void;

    /**
     * @return array{principalIdentifier: ?string, identityIdentifier: ?string, isDelegatedPrincipal: ?bool, isEnabled: ?bool}
     */
    public function toArray(): array;
}

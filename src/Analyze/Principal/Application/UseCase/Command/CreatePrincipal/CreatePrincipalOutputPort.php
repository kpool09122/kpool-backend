<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Analyze\Principal\Domain\Entity\Principal;

interface CreatePrincipalOutputPort
{
    public function setPrincipal(Principal $principal): void;

    public function principal(): ?Principal;

    /** @return array<string, mixed> */
    public function toArray(): array;
}

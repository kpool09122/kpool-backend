<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;

interface CreatePrincipalGroupOutputPort
{
    public function setPrincipalGroup(PrincipalGroup $principalGroup): void;

    /**
     * @return array{principalGroupIdentifier: ?string, accountIdentifier: ?string, name: ?string, isDefault: ?bool, memberCount: ?int, createdAt: ?string}
     */
    public function toArray(): array;
}

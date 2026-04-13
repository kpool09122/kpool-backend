<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class PrincipalResolver
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    public function resolve(IdentityIdentifier $identityIdentifier): PrincipalIdentifier
    {
        $principal = $this->principalRepository->findByIdentityIdentifier($identityIdentifier);
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        return $principal->principalIdentifier();
    }
}

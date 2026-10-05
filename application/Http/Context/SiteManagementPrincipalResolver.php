<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

readonly class SiteManagementPrincipalResolver
{
    public function __construct(private PrincipalRepositoryInterface $principalRepository)
    {
    }

    public function resolve(ActorContext $actorContext): PrincipalIdentifier
    {
        $principal = $this->principalRepository->findByIdentityId($actorContext->identityIdentifier);
        if ($principal === null) {
            throw new UnauthorizedException();
        }

        return $principal->principalIdentifier();
    }
}

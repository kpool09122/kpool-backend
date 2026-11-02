<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AnalyzePrincipalResolver
{
    public function __construct(private PrincipalRepositoryInterface $principalRepository)
    {
    }

    /** @throws UnauthorizedException */
    public function resolve(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): PrincipalIdentifier
    {
        $principal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier($identityIdentifier, $accountIdentifier);

        return $principal?->principalIdentifier() ?? throw new UnauthorizedException();
    }
}

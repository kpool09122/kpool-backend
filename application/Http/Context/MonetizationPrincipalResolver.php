<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Shared\Domain\Exception\PrincipalNotFoundException;

readonly class MonetizationPrincipalResolver
{
    public function __construct(private PrincipalRepositoryInterface $principalRepository)
    {
    }

    public function resolve(AccountContext $accountContext): PrincipalIdentifier
    {
        $principal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier(
            $accountContext->originalIdentityIdentifier(),
            $accountContext->principal()->accountIdentifier(),
        );
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        return $principal->principalIdentifier();
    }
}

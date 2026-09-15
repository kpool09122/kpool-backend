<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class PrincipalResolver
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

<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Query\Authorization;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class PrincipalGroupManageAuthorization
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    /** @throws AccountUpdateForbiddenException */
    public function assertAllowed(AccountIdentifier $accountIdentifier, Principal $principal): void
    {
        if ((string) $principal->accountIdentifier() !== (string) $accountIdentifier) {
            throw new AccountUpdateForbiddenException();
        }

        if (! $this->policyEvaluator->evaluate($principal, Action::PRINCIPAL_GROUP_MANAGE, Resource::account($accountIdentifier))) {
            throw new AccountUpdateForbiddenException();
        }
    }
}

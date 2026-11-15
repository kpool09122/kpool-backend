<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationAccountServiceInterface
{
    /** @throws AuthorizationAccountUnavailableException */
    public function resolveOriginalAccount(IdentityIdentifier $identityIdentifier): AuthorizationAccount;

    public function allowsDelegatedTarget(AuthorizationAccount $originalAccount, AccountIdentifier $accountIdentifier, DelegationIdentifier $delegationIdentifier): bool;
}

<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AccountMembershipServiceInterface
{
    public function isAccountOwner(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): bool;

    public function belongsToActiveAccount(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): bool;
}

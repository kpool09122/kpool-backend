<?php

declare(strict_types=1);

namespace Tests\Helper;

use Application\Http\Context\AccountContext;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

final class CreateAccountContext
{
    public static function create(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): AccountContext
    {
        return new AccountContext(
            new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identityIdentifier, $accountIdentifier),
            null,
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
        );
    }
}

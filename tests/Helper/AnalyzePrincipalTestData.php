<?php

declare(strict_types=1);

namespace Tests\Helper;

use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\Entity\PrincipalGroup;
use Source\Analyze\Principal\Domain\Entity\Role;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Analyze\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AnalyzePrincipalTestData
{
    public function __construct(public Principal $principal, public PrincipalGroup $group, public Role $role)
    {
    }

    public static function create(): self
    {
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');

        return new self(
            new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $account, PrincipalStatus::ACTIVE),
            new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'General', [], $account, true, null, []),
            new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'General', [], null),
        );
    }
}

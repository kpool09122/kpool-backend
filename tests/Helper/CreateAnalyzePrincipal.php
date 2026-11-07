<?php

declare(strict_types=1);

namespace Tests\Helper;

use Database\Seeders\AnalyzeAuthorizationSeeder;
use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\Entity\PrincipalGroup;
use Source\Analyze\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Analyze\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class CreateAnalyzePrincipal
{
    public static function create(): Principal
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        CreateAccount::create((string) $account);
        app(AnalyzeAuthorizationSeeder::class)->run();
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identity, $account, PrincipalStatus::ACTIVE);
        app(PrincipalRepositoryInterface::class)->save($principal);
        app(PrincipalGroupRepositoryInterface::class)->save(new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            'General',
            [new RoleIdentifier('08370000-0000-7000-8000-000000000002')],
            $account,
            true,
            null,
            [$principal->principalIdentifier()],
        ));

        return $principal;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\AuthorizationAccount;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AuthorizationAccountTest extends TestCase
{
    public function testCarriesTheOriginalAccountDetails(): void
    {
        $identity = new IdentityIdentifier('0199f700-0000-7000-8000-000000000001');
        $account = new AccountIdentifier('0199f700-0000-7000-8000-000000000002');
        $principal = new AccountPrincipalIdentifier('0199f700-0000-7000-8000-000000000003');
        $authorizationAccount = new AuthorizationAccount($identity, $account, $principal, 'Original');

        self::assertSame($identity, $authorizationAccount->identityIdentifier);
        self::assertSame($account, $authorizationAccount->accountIdentifier);
        self::assertSame($principal, $authorizationAccount->principalIdentifier);
        self::assertSame('Original', $authorizationAccount->name);
    }
}

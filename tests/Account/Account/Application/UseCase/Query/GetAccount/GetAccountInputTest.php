<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\GetAccount;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\GetAccount\GetAccountInput;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class GetAccountInputTest extends TestCase
{
    public function test__construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );

        $input = new GetAccountInput($accountIdentifier, $principal, AccountType::CORPORATION);

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame($principal, $input->principal());
        $this->assertSame(AccountType::CORPORATION, $input->accountType());
    }
}

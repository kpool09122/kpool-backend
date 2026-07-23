<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\UpdateAccount;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInput;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class UpdateAccountInputTest extends TestCase
{
    public function test__construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );
        $accountName = new AccountName('Updated Account');

        $input = new UpdateAccountInput($accountIdentifier, $principal, $accountName);

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame($principal, $input->principal());
        $this->assertSame($accountName, $input->accountName());
    }
}

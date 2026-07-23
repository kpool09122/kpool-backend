<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\UpdateAccount;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInput;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class UpdateAccountInputTest extends TestCase
{
    public function test__construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $actorIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountName = new AccountName('Updated Account');

        $input = new UpdateAccountInput($accountIdentifier, $actorIdentityIdentifier, $accountName);

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame($actorIdentityIdentifier, $input->actorIdentityIdentifier());
        $this->assertSame($accountName, $input->accountName());
    }
}

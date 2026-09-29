<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\CompleteInitialSetup;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup\CompleteInitialSetupInput;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class CompleteInitialSetupInputTest extends TestCase
{
    public function testAccessorsPreserveSuppliedValueObjects(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $accountType = AccountType::CORPORATION;
        $input = new CompleteInitialSetupInput($accountIdentifier, $accountType);

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame($accountType, $input->accountType());
    }
}

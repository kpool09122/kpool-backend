<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccountInput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class ProvisionMonetizationAccountInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new ProvisionMonetizationAccountInput($accountIdentifier);

        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationInput;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class PrepareCardRegistrationInputTest extends TestCase
{
    public function testPreservesExactInputInstances(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new PrepareCardRegistrationInput($account, $principal);
        self::assertSame($account, $input->accountIdentifier());
        self::assertSame($principal, $input->operatorPrincipalIdentifier());
    }
}

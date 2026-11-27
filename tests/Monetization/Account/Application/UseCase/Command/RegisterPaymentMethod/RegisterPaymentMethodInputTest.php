<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class RegisterPaymentMethodInputTest extends TestCase
{
    public function testPreservesExactInputInstances(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identifier = new SetupIntentIdentifier('seti_test123');
        $input = new RegisterPaymentMethodInput($account, $identifier, $principal);
        self::assertSame($account, $input->accountIdentifier());
        self::assertSame($principal, $input->operatorPrincipalIdentifier());
        self::assertSame($identifier, $input->setupIntentIdentifier());
    }
}

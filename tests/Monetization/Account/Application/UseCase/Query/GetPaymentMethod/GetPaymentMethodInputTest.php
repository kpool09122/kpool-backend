<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Query\GetPaymentMethod;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod\GetPaymentMethodInput;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class GetPaymentMethodInputTest extends TestCase
{
    public function testPreservesExactInputInstances(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identifier = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        $input = new GetPaymentMethodInput($account, $identifier, $principal);
        self::assertSame($account, $input->accountIdentifier());
        self::assertSame($principal, $input->operatorPrincipalIdentifier());
        self::assertSame($identifier, $input->paymentMethodIdentifier());
    }
}

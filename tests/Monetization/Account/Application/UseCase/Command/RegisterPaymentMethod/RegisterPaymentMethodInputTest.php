<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;

class RegisterPaymentMethodInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $externalPaymentMethodId = new ExternalPaymentMethodId('pm_sample_value');
        $type = PaymentMethodType::CARD;

        $principalIdentifier = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000002');
        $subject = new RegisterPaymentMethodInput($monetizationAccountIdentifier, $externalPaymentMethodId, $type, $principalIdentifier);

        $this->assertSame($monetizationAccountIdentifier, $subject->monetizationAccountIdentifier());
        $this->assertSame($externalPaymentMethodId, $subject->externalPaymentMethodId());
        $this->assertSame($principalIdentifier, $subject->operatorPrincipalIdentifier());
        $this->assertSame($type, $subject->type());
    }
}

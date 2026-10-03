<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

class RegisterPaymentMethodInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $paymentMethodId = new PaymentMethodId('pm_sample_value');
        $type = PaymentMethodType::CARD;

        $subject = new RegisterPaymentMethodInput($monetizationAccountIdentifier, $paymentMethodId, $type);

        $this->assertSame($monetizationAccountIdentifier, $subject->monetizationAccountIdentifier());
        $this->assertSame($paymentMethodId, $subject->paymentMethodId());
        $this->assertSame($type, $subject->type());
    }
}

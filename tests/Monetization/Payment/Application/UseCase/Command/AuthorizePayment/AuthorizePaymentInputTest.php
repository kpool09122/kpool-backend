<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInput;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AuthorizePaymentInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $buyerMonetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $money = new Money(1000, Currency::JPY);
        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            PaymentMethodType::CARD,
            'Visa **** 1234',
            true,
        );

        $input = new AuthorizePaymentInput($orderIdentifier, $buyerMonetizationAccountIdentifier, $money, $paymentMethod);

        $this->assertSame($orderIdentifier, $input->orderIdentifier());
        $this->assertSame($buyerMonetizationAccountIdentifier, $input->buyerMonetizationAccountIdentifier());
        $this->assertSame($money, $input->money());
        $this->assertSame($paymentMethod, $input->paymentMethod());
    }
}

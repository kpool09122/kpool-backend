<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInput;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
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
        $money = new Money(1000, Currency::JPY);
        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUlid()),
            PaymentMethodType::CARD,
            'Visa **** 1234',
            true,
        );

        $input = new AuthorizePaymentInput($money, $paymentMethod);

        $this->assertSame($money, $input->money());
        $this->assertSame($paymentMethod, $input->paymentMethod());
    }
}

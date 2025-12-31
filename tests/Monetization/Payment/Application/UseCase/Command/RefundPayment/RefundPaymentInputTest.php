<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPaymentInput;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RefundPaymentInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUuid());
        $refundAmount = new Money(500, Currency::JPY);
        $reason = 'customer_request';

        $input = new RefundPaymentInput($paymentIdentifier, $refundAmount, $reason);

        $this->assertSame($paymentIdentifier, $input->paymentIdentifier());
        $this->assertSame($refundAmount, $input->refundAmount());
        $this->assertSame($reason, $input->reason());
    }
}

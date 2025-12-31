<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\CapturePayment;

use Source\Monetization\Payment\Application\UseCase\Command\CapturePayment\CapturePaymentInput;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CapturePaymentInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUuid());

        $input = new CapturePaymentInput($paymentIdentifier);

        $this->assertSame($paymentIdentifier, $input->paymentIdentifier());
    }
}

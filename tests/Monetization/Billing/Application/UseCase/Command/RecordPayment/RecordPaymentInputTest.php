<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Application\UseCase\Command\RecordPayment;

use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPaymentInput;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RecordPaymentInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $invoiceIdentifier = new InvoiceIdentifier(StrTestHelper::generateUlid());
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUlid());

        $input = new RecordPaymentInput($invoiceIdentifier, $paymentIdentifier);

        $this->assertSame($invoiceIdentifier, $input->invoiceIdentifier());
        $this->assertSame($paymentIdentifier, $input->paymentIdentifier());
    }
}

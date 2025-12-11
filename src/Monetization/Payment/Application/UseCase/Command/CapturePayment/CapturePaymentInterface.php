<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\CapturePayment;

use Source\Monetization\Payment\Domain\Entity\Payment;

interface CapturePaymentInterface
{
    public function process(CapturePaymentInputPort $input): Payment;
}

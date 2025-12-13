<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use Source\Monetization\Payment\Domain\Entity\Payment;

interface RefundPaymentInterface
{
    public function process(RefundPaymentInputPort $input): Payment;
}

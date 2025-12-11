<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Payment\Domain\Entity\Payment;

interface AuthorizePaymentInterface
{
    public function process(AuthorizePaymentInputPort $input): Payment;
}

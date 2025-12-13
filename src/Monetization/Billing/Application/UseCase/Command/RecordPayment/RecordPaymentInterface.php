<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\RecordPayment;

use Source\Monetization\Billing\Domain\Entity\Invoice;

interface RecordPaymentInterface
{
    public function process(RecordPaymentInputPort $input): Invoice;
}

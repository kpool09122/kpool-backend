<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\RecordPayment;

interface RecordPaymentInterface
{
    public function process(RecordPaymentInputPort $input, RecordPaymentOutputPort $output): void;
}

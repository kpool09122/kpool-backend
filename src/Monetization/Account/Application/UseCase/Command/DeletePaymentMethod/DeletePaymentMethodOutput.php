<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod;

class DeletePaymentMethodOutput implements DeletePaymentMethodOutputPort
{
    /** @return array{} */
    public function toArray(): array
    {
        return [];
    }
}

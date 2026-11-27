<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Query;

abstract readonly class PaymentMethodDetailsReadModel
{
    /** @return array<string, mixed> */
    abstract public function toArray(): array;
}

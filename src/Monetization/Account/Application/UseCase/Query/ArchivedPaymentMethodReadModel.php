<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Query;

readonly class ArchivedPaymentMethodReadModel extends PaymentMethodDetailsReadModel
{
    public function __construct(
        public string $paymentMethodIdentifier,
        public string $type,
        public ?string $brand,
        public ?string $last4,
        public string $archivedAt,
    ) {
    }

    /** @return array{paymentMethodIdentifier: string, type: string, brand: ?string, last4: ?string, archivedAt: string} */
    public function toArray(): array
    {
        return [
            'paymentMethodIdentifier' => $this->paymentMethodIdentifier,
            'type' => $this->type,
            'brand' => $this->brand,
            'last4' => $this->last4,
            'archivedAt' => $this->archivedAt,
        ];
    }
}

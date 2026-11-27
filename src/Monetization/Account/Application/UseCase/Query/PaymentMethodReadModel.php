<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Query;

readonly class PaymentMethodReadModel extends PaymentMethodDetailsReadModel
{
    public function __construct(
        public string $paymentMethodIdentifier,
        public string $type,
        public ?string $brand,
        public ?string $last4,
        public ?int $expMonth,
        public ?int $expYear,
        public bool $isDefault,
        public string $status,
    ) {
    }

    /** @return array{paymentMethodIdentifier:string,type:string,brand:?string,last4:?string,expMonth:?int,expYear:?int,isDefault:bool,status:string,archivedAt:null} */
    public function toArray(): array
    {
        return ['paymentMethodIdentifier' => $this->paymentMethodIdentifier,'type' => $this->type,'brand' => $this->brand,'last4' => $this->last4,'expMonth' => $this->expMonth,'expYear' => $this->expYear,'isDefault' => $this->isDefault,'status' => $this->status,'archivedAt' => null];
    }
}

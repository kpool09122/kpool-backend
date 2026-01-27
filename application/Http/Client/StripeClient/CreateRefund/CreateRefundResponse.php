<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateRefund;

final readonly class CreateRefundResponse
{
    public function __construct(
        private string $id,
        private string $status,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function status(): string
    {
        return $this->status;
    }
}

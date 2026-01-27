<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateTransfer;

final readonly class CreateTransferResponse
{
    public function __construct(
        private string $id,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }
}

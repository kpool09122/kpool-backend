<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\RetrieveAccount;

final readonly class RetrieveAccountRequest
{
    public function __construct(
        private string $accountId,
    ) {
    }

    public function accountId(): string
    {
        return $this->accountId;
    }
}

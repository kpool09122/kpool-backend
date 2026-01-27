<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateConnectedAccount;

final readonly class CreateConnectedAccountRequest
{
    public function __construct(
        private string $email,
        private string $country,
    ) {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function country(): string
    {
        return $this->country;
    }
}

<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateConnectedAccount;

final readonly class CreateConnectedAccountResponse
{
    public function __construct(
        private string $id,
    ) {
    }

    public function params(): CreateConnectedAccountParams
    {
        return CreateConnectedAccountParams::fromArray(['id' => $this->id]);
    }

    public function id(): string
    {
        return $this->id;
    }
}

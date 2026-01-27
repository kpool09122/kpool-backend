<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateAccountLink;

final readonly class CreateAccountLinkRequest
{
    public function __construct(
        private string $accountId,
        private string $refreshUrl,
        private string $returnUrl,
    ) {
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function refreshUrl(): string
    {
        return $this->refreshUrl;
    }

    public function returnUrl(): string
    {
        return $this->returnUrl;
    }
}

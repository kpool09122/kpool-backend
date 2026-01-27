<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateAccountLink;

final readonly class CreateAccountLinkResponse
{
    public function __construct(
        private string $url,
    ) {
    }

    public function params(): CreateAccountLinkParams
    {
        return CreateAccountLinkParams::fromArray(['url' => $this->url]);
    }

    public function url(): string
    {
        return $this->url;
    }
}

<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateAccountLink;

final readonly class CreateAccountLinkParams
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        private array $params,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromArray(array $params): self
    {
        return new self($params);
    }

    public function url(): string
    {
        /** @var string $url */
        $url = $this->params['url'];

        return $url;
    }
}

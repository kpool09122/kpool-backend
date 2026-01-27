<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\FetchUserInfo;

final readonly class FetchUserInfoParams
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

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->params;
    }
}

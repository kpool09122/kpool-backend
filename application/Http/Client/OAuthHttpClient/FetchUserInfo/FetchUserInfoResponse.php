<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\FetchUserInfo;

final readonly class FetchUserInfoResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }
}

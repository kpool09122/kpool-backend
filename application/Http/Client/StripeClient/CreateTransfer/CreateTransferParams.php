<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateTransfer;

final readonly class CreateTransferParams
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

    public function id(): string
    {
        /** @var string $id */
        $id = $this->params['id'];

        return $id;
    }
}

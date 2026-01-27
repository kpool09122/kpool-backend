<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateRefund;

final readonly class CreateRefundParams
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

    public function status(): string
    {
        /** @var string $status */
        $status = $this->params['status'];

        return $status;
    }
}

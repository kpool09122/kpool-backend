<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\RetrieveAccount;

final readonly class RetrieveAccountParams
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

    public function detailsSubmitted(): bool
    {
        /** @var bool $detailsSubmitted */
        $detailsSubmitted = $this->params['details_submitted'] ?? false;

        return $detailsSubmitted;
    }

    public function disabledReason(): ?string
    {
        /** @var string|null $disabledReason */
        $disabledReason = $this->params['disabled_reason'] ?? null;

        return $disabledReason;
    }

    public function chargesEnabled(): bool
    {
        /** @var bool $chargesEnabled */
        $chargesEnabled = $this->params['charges_enabled'] ?? false;

        return $chargesEnabled;
    }

    public function payoutsEnabled(): bool
    {
        /** @var bool $payoutsEnabled */
        $payoutsEnabled = $this->params['payouts_enabled'] ?? false;

        return $payoutsEnabled;
    }
}

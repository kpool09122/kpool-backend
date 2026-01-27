<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\RetrieveAccount;

final readonly class RetrieveAccountResponse
{
    public function __construct(
        private bool $detailsSubmitted,
        private ?string $disabledReason,
        private bool $chargesEnabled,
        private bool $payoutsEnabled,
    ) {
    }

    public function params(): RetrieveAccountParams
    {
        return RetrieveAccountParams::fromArray([
            'details_submitted' => $this->detailsSubmitted,
            'disabled_reason' => $this->disabledReason,
            'charges_enabled' => $this->chargesEnabled,
            'payouts_enabled' => $this->payoutsEnabled,
        ]);
    }

    public function detailsSubmitted(): bool
    {
        return $this->detailsSubmitted;
    }

    public function disabledReason(): ?string
    {
        return $this->disabledReason;
    }

    public function chargesEnabled(): bool
    {
        return $this->chargesEnabled;
    }

    public function payoutsEnabled(): bool
    {
        return $this->payoutsEnabled;
    }
}

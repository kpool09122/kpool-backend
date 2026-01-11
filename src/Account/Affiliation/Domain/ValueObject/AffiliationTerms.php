<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Domain\ValueObject;

use Source\Monetization\Shared\ValueObject\Percentage;

readonly class AffiliationTerms
{
    public function __construct(
        private ?Percentage $revenueSharePercentage,
        private ?string $contractNotes,
    ) {
    }

    public function revenueSharePercentage(): ?Percentage
    {
        return $this->revenueSharePercentage;
    }

    public function contractNotes(): ?string
    {
        return $this->contractNotes;
    }
}

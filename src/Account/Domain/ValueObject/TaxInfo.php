<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

readonly class TaxInfo
{
    public function __construct(
        private TaxRegion   $region,
        private TaxCategory $category,
        private ?string     $taxCode = null,
    ) {
    }

    public function region(): TaxRegion
    {
        return $this->region;
    }

    public function category(): TaxCategory
    {
        return $this->category;
    }

    public function taxCode(): ?string
    {
        return $this->taxCode;
    }
}

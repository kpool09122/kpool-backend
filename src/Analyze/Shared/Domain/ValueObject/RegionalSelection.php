<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

/** null selects the country; empty string selects its unknown region. */
readonly class RegionalSelection
{
    public ?string $region;

    public function __construct(public CountryCode $country, ?string $region = null)
    {
        $this->region = $region !== null && $region !== '' ? (string) new SubdivisionCode($country, $region) : $region;
    }
}

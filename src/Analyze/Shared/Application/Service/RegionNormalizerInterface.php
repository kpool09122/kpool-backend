<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Application\Service;

use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

interface RegionNormalizerInterface
{
    public function normalize(CountryCode $country, string $name): ?SubdivisionCode;
}

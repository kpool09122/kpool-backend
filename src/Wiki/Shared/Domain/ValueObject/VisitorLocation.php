<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;

readonly class VisitorLocation
{
    private ?string $region;

    public function __construct(private ?string $country = null, ?string $region = null)
    {
        if ($country !== null && preg_match('/\A[A-Z]{2}\z/', $country) !== 1) {
            throw new InvalidArgumentException('Invalid visitor country code.');
        }
        if ($country !== null && $region !== null && preg_match('/\A[A-Z0-9]{1,13}\z/', $region) !== 1) {
            throw new InvalidArgumentException('Invalid visitor region code.');
        }
        $this->region = $country === null ? null : $region;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function region(): ?string
    {
        return $this->region;
    }
}

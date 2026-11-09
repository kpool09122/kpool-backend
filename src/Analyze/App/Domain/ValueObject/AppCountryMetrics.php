<?php

declare(strict_types=1);

namespace Source\Analyze\App\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\CountryCode;

/** A country breakdown acquired with the global daily snapshot, never a replacement for it. */
readonly class AppCountryMetrics
{
    public function __construct(
        public ?CountryCode $country,
        public ApplicationUserCount $applicationUsers,
        public RegisteredUserSnapshot $registeredUsers,
    ) {
    }

    public function countryKey(): string
    {
        return $this->country === null ? '' : $this->country->value;
    }
}

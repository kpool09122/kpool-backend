<?php

declare(strict_types=1);

namespace Source\Analyze\App\Domain\Entity;

use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\App\Domain\ValueObject\AppCountryMetrics;
use Source\Analyze\App\Domain\ValueObject\AppDailySummaryIdentifier;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

class AppDailySummary
{
    /** @throws InvalidDailySummaryException */
    /** @param ?list<AppCountryMetrics> $countries */
    public function __construct(
        private readonly AppDailySummaryIdentifier $identifier,
        private readonly UtcDate $day,
        private ApplicationUserCount $applicationUsers,
        private RegisteredUserSnapshot $registeredUsers,
        private ?array $countries = null,
    ) {
        $this->validateAnalysis($applicationUsers, $registeredUsers);
        $this->replaceCountries($countries);
    }

    /** @throws InvalidDailySummaryException */
    public function replaceAnalysis(ApplicationUserCount $applicationUsers, RegisteredUserSnapshot $registeredUsers): void
    {
        $this->validateAnalysis($applicationUsers, $registeredUsers);
        $this->applicationUsers = $applicationUsers;
        $this->registeredUsers = $registeredUsers;
    }

    /** @param ?list<AppCountryMetrics> $countries
     * @throws InvalidDailySummaryException
     */
    public function replaceCountries(?array $countries): void
    {
        $seen = [];
        foreach ($countries ?? [] as $row) {
            $key = $row->countryKey();
            if (isset($seen[$key])) {
                throw new InvalidDailySummaryException('Duplicate App country.');
            }
            $seen[$key] = true;
            $this->validateAnalysis($row->applicationUsers, $row->registeredUsers);
        }
        $this->countries = $countries;
    }

    /** @return ?list<AppCountryMetrics> */
    public function countries(): ?array
    {
        return $this->countries;
    }

    public function applicationUsers(): ApplicationUserCount
    {
        return $this->applicationUsers;
    }

    public function registeredUsers(): RegisteredUserSnapshot
    {
        return $this->registeredUsers;
    }

    public function identifier(): AppDailySummaryIdentifier
    {
        return $this->identifier;
    }

    public function day(): UtcDate
    {
        return $this->day;
    }

    /** @throws InvalidDailySummaryException */
    private function validateAnalysis(ApplicationUserCount $applicationUsers, RegisteredUserSnapshot $registeredUsers): void
    {
        if ((string) $applicationUsers->provenance()->day() !== (string) $this->day) {
            throw new InvalidDailySummaryException('Analytical values must belong to the summary day.');
        }
        if ((string) $registeredUsers->provenance()->day() !== (string) $this->day) {
            throw new InvalidDailySummaryException('Analytical values must belong to the summary day.');
        }
    }
}

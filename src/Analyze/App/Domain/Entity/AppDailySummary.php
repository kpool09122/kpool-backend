<?php

declare(strict_types=1);

namespace Source\Analyze\App\Domain\Entity;

use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\App\Domain\ValueObject\AppCountryMetrics;
use Source\Analyze\App\Domain\ValueObject\AppDailySummaryIdentifier;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

class AppDailySummary
{
    /**
     * @param list<AppCountryMetrics> $countries
     * @param list<RegionalMetricObservation> $regions
     * @throws InvalidDailySummaryException
     */
    public function __construct(
        private readonly AppDailySummaryIdentifier $identifier,
        private readonly UtcDate $day,
        private ApplicationUserCount $applicationUsers,
        private RegisteredUserSnapshot $registeredUsers,
        private array $countries,
        private array $regions = [],
    ) {
        $this->validateAnalysis($applicationUsers, $registeredUsers);
        $this->validateCountries($countries);
        $this->validateRegions($regions);
    }

    /**
     * @param list<AppCountryMetrics> $countries
     * @param list<RegionalMetricObservation> $regions
     * @throws InvalidDailySummaryException
     */
    public function replaceAnalysis(ApplicationUserCount $applicationUsers, RegisteredUserSnapshot $registeredUsers, array $countries, array $regions): void
    {
        $this->validateAnalysis($applicationUsers, $registeredUsers);
        $this->validateCountries($countries);
        $this->validateRegions($regions);
        $this->applicationUsers = $applicationUsers;
        $this->registeredUsers = $registeredUsers;
        $this->countries = $countries;
        $this->regions = $regions;
    }

    /** @return list<AppCountryMetrics> */
    public function countries(): array
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

    /** @return list<RegionalMetricObservation> */
    public function regions(): array
    {
        return $this->regions;
    }

    /**
     * @param list<RegionalMetricObservation> $regions
     * @throws InvalidDailySummaryException
     */
    private function validateRegions(array $regions): void
    {
        $seen = [];
        foreach ($regions as $row) {
            if ($row->wikiIdentifier !== '' || $row->translationSetIdentifier !== '' || $row->language !== '' || ! in_array($row->metric, ['application_users', 'registered_users'], true)) {
                throw new InvalidDailySummaryException('Regional measurements must belong to this summary.');
            }
            $key = implode("\0", [$row->country, $row->region, $row->metric]);
            if (isset($seen[$key])) {
                throw new InvalidDailySummaryException('Duplicate regional measurement.');
            }
            $seen[$key] = true;
        }
    }

    public function identifier(): AppDailySummaryIdentifier
    {
        return $this->identifier;
    }

    public function day(): UtcDate
    {
        return $this->day;
    }

    /** @param list<AppCountryMetrics> $countries
     * @throws InvalidDailySummaryException
     */
    private function validateCountries(array $countries): void
    {
        $seen = [];
        foreach ($countries as $row) {
            $key = $row->countryKey();
            if (isset($seen[$key])) {
                throw new InvalidDailySummaryException('Duplicate App country.');
            }
            $seen[$key] = true;
            $this->validateAnalysis($row->applicationUsers, $row->registeredUsers);
        }
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

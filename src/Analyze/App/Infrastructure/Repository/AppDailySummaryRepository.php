<?php

declare(strict_types=1);

namespace Source\Analyze\App\Infrastructure\Repository;

use Application\Models\Analyze\App\AppCountryDailySummary as CountryModel;
use Application\Models\Analyze\App\AppDailySummary as SummaryModel;
use DateTimeImmutable;
use Source\Analyze\App\Domain\Entity\AppDailySummary;
use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\App\Domain\Repository\AppDailySummaryRepositoryInterface;
use Source\Analyze\App\Domain\ValueObject\AppCountryMetrics;
use Source\Analyze\App\Domain\ValueObject\AppDailySummaryIdentifier;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Domain\ValueObject\CountryCode;
use Symfony\Component\Uid\Uuid;

/** @phpstan-import-type StoredProvenance from SummaryModel */
readonly class AppDailySummaryRepository implements AppDailySummaryRepositoryInterface
{
    /**
     * @throws InvalidDailySummaryException
     * @throws MetricDefinitionMismatchException
     */
    public function findByDay(UtcDate $day): ?AppDailySummary
    {
        $row = SummaryModel::query()->where('day', (string) $day)->first();
        if ($row === null) {
            return null;
        }
        $provenance = [];
        foreach ([Metric::APPLICATION_USERS, Metric::REGISTERED_USERS] as $metric) {
            $metadata = $row->measurements[$metric->value] ?? throw new MetricDefinitionMismatchException('Summary metric provenance is missing.');
            $provenance[$metric->value] = $this->restoreProvenance($metadata, $day);
        }

        $countryRows = CountryModel::query()->where('day', (string) $day)->get();
        $registeredTotal = CountryModel::query()->where('day', (string) $day)->sum('registered_users');
        $countries = [];
        foreach ($countryRows as $countryRow) {
            $countries[] = new AppCountryMetrics(
                $countryRow->country === '' ? null : CountryCode::from($countryRow->country),
                new ApplicationUserCount($this->count($countryRow->application_users), $provenance[Metric::APPLICATION_USERS->value]),
                new RegisteredUserSnapshot($this->count($countryRow->registered_users), $day->next()->instant(), $provenance[Metric::REGISTERED_USERS->value]),
            );
        }

        return new AppDailySummary(
            new AppDailySummaryIdentifier($row->id),
            $day,
            countries: $countries,
            applicationUsers: new ApplicationUserCount($this->count($row->application_users), $provenance[Metric::APPLICATION_USERS->value]),
            registeredUsers: new RegisteredUserSnapshot($this->count((string) $registeredTotal), $day->next()->instant(), $provenance[Metric::REGISTERED_USERS->value]),
        );
    }

    public function save(AppDailySummary $summary): void
    {
        $metadata = [];
        $provenance = [
            $summary->applicationUsers()->provenance(),
            $summary->registeredUsers()->provenance(),
        ];
        foreach ($provenance as $origin) {
            $metadata[$origin->definition()->metric->value] = $this->serializeProvenance($origin);
        }
        SummaryModel::query()->updateOrCreate(['id' => (string) $summary->identifier()], [
            'day' => (string) $summary->day(),
            'revision' => (string) Uuid::v7(),
            'application_users' => $summary->applicationUsers()->count(),
            'measurements' => $metadata,
        ]);
        CountryModel::query()->where('day', (string) $summary->day())->delete();
        foreach ($summary->countries() as $country) {
            CountryModel::query()->create([
                'id' => (string) Uuid::v7(), 'day' => (string) $summary->day(), 'country' => $country->countryKey(),
                'application_users' => $country->applicationUsers->count(), 'registered_users' => $country->registeredUsers->count(),
            ]);
        }
    }

    /** @throws InvalidDailySummaryException */
    private function count(?string $value): string
    {
        if ($value === null) {
            throw new InvalidDailySummaryException('A required count is missing.');
        }

        return preg_replace('/\.0+$/', '', $value) ?? $value;
    }

    /** @return StoredProvenance
     */
    private function serializeProvenance(MetricProvenance $provenance): array
    {
        $definition = $provenance->definition();

        return [
            'source' => $definition->source, 'metric' => $definition->metric->value,
            'grain' => $definition->grain, 'version' => $definition->version,
            'quality' => $provenance->quality(), 'updated_at' => $provenance->updatedAt()->format('Y-m-d\TH:i:s.uP'),
        ];
    }

    /** @param StoredProvenance $metadata
     */
    private function restoreProvenance(array $metadata, UtcDate $day): MetricProvenance
    {
        $definition = new MetricDefinition(
            $metadata['source'],
            Metric::from($metadata['metric']),
            $metadata['grain'],
            $metadata['version'],
        );

        return new MetricProvenance($definition, $day, new DateTimeImmutable($metadata['updated_at']), $metadata['quality']);
    }
}

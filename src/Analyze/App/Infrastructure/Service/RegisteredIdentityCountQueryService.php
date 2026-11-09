<?php

declare(strict_types=1);

namespace Source\Analyze\App\Infrastructure\Service;

use Application\Models\Identity\ArchivedIdentity;
use Application\Models\Identity\Identity;
use DateTimeImmutable;
use Source\Analyze\App\Application\Service\RegisteredIdentityCountQueryServiceInterface;

readonly class RegisteredIdentityCountQueryService implements RegisteredIdentityCountQueryServiceInterface
{
    /** @return list<array{country: string, region: string, total: string}> */
    public function countByRegionBefore(DateTimeImmutable $endExclusive): array
    {
        $active = Identity::query()->where('created_at', '<', $endExclusive)->select('registration_country', 'registration_region');
        $archived = ArchivedIdentity::query()->where('identity_created_at', '<', $endExclusive)->where('archived_at', '>=', $endExclusive)->select('registration_country', 'registration_region');
        $rows = Identity::query()->fromSub($active->unionAll($archived), 'registered')
            ->selectRaw("COALESCE(registration_country, '') AS country, COALESCE(registration_region, '') AS region, COUNT(*)::text AS total")
            ->groupByRaw("COALESCE(registration_country, ''), COALESCE(registration_region, '')")->get();
        $counts = [];
        /** @var Identity&object{country: string, region: string, total: string} $row */
        foreach ($rows as $row) {
            $counts[] = ['country' => $row->country, 'region' => $row->region, 'total' => $row->total];
        }

        return $counts;
    }

    /** @return array<string, string> */
    public function countByCountryBefore(DateTimeImmutable $endExclusive): array
    {
        $active = Identity::query()->where('created_at', '<', $endExclusive)->select('registration_country');
        $withdrawn = ArchivedIdentity::query()->where('identity_created_at', '<', $endExclusive)->where('archived_at', '>=', $endExclusive)->select('registration_country');
        $rows = Identity::query()->fromSub($active->unionAll($withdrawn), 'registered')
            ->selectRaw("COALESCE(registration_country, '') AS country, COUNT(*)::text AS total")
            ->groupByRaw("COALESCE(registration_country, '')")->get();
        $counts = [];
        /** @var Identity&object{country: string, total: string} $row */
        foreach ($rows as $row) {
            $counts[(string) $row->country] = (string) $row->total;
        }
        $counts[''] ??= '0';

        return $counts;
    }
}

<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiAgencySummaryReadModel;

final readonly class WikiAgencySummaryResolver
{
    public static function resolve(?string $wikiIdentifier): ?WikiAgencySummaryReadModel
    {
        if ($wikiIdentifier === null) {
            return null;
        }

        $agency = WikiModel::query()
            ->with('agencyBasic')
            ->where('id', $wikiIdentifier)
            ->where('resource_type', ResourceType::AGENCY->value)
            ->first();

        if ($agency?->agencyBasic === null) {
            return null;
        }

        return new WikiAgencySummaryReadModel(
            wikiIdentifier: $agency->id,
            slug: $agency->slug,
            language: $agency->language,
            name: $agency->agencyBasic->name,
            normalizedName: $agency->agencyBasic->normalized_name,
        );
    }
}

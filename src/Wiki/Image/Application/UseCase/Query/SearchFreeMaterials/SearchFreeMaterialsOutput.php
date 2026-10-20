<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials;

use Source\Wiki\Image\Application\UseCase\Query\FreeMaterialSiteReadModel;

final class SearchFreeMaterialsOutput implements SearchFreeMaterialsOutputPort
{
    /** @var list<FreeMaterialSiteReadModel> */
    private array $sites = [];

    /** @param list<FreeMaterialSiteReadModel> $sites */
    public function output(array $sites): void
    {
        $this->sites = $sites;
    }

    /** @return array{sites: list<array{siteName: string, pageUrl: string, reason: string, usageEvidence: string, usageEvidenceUrl: string}>} */
    public function toArray(): array
    {
        return ['sites' => array_map(static fn (FreeMaterialSiteReadModel $site): array => $site->toArray(), $this->sites)];
    }
}

<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations;

use Source\Account\Affiliation\Application\UseCase\Query\AffiliationReadModel;

interface ListAffiliationsOutputPort
{
    /** @param AffiliationReadModel[] $affiliations */
    public function output(array $affiliations, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /** @return array{affiliations: array<int, array<string, mixed>>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null} */
    public function toArray(): array;
}

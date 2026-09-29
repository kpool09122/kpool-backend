<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations;

use Source\Account\Affiliation\Application\UseCase\Query\AffiliationReadModel;

interface ListAffiliationsOutputPort
{
    /** @param AffiliationReadModel[] $affiliations */
    public function output(array $affiliations, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /** @return array{affiliations: array<array{ affiliationIdentifier: string, agencyAccountIdentifier: string, talentAccountIdentifier: string, agencyAccount: array{accountIdentifier: string, name: string, email: string}, talentAccount: array{accountIdentifier: string, name: string, email: string}, requestedBy: string, status: string, terms: array{revenueSharePercentage: int|null, contractNotes: string|null}|null, requestedAt: string, activatedAt: string|null, terminatedAt: string|null }>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null} */
    public function toArray(): array;
}

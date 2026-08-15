<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations;

use Source\Account\Affiliation\Application\UseCase\Query\AffiliationReadModel;

class ListAffiliationsOutput implements ListAffiliationsOutputPort
{
    /** @var AffiliationReadModel[] */
    private array $affiliations = [];

    private ?int $currentPage = null;

    private ?int $lastPage = null;

    private ?int $total = null;

    private ?int $perPage = null;

    /** @param AffiliationReadModel[] $affiliations */
    public function output(array $affiliations, int $currentPage, int $lastPage, int $total, int $perPage): void
    {
        $this->affiliations = $affiliations;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    /** @return array{affiliations: array<int, array<string, mixed>>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null} */
    public function toArray(): array
    {
        return [
            'affiliations' => array_map(
                static fn (AffiliationReadModel $affiliation): array => $affiliation->toArray(),
                $this->affiliations,
            ),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}

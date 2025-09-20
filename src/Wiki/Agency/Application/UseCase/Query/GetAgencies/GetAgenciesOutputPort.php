<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Query\GetAgencies;

use Source\Wiki\Agency\Application\UseCase\Query\AgencyReadModel;

interface GetAgenciesOutputPort
{
    /**
     * @param AgencyReadModel[] $agencies
     * @param int $currentPage
     * @param int $lastPage
     * @param int $total
     * @return void
     */
    public function output(
        array $agencies,
        int $currentPage,
        int $lastPage,
        int $total,
    ): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

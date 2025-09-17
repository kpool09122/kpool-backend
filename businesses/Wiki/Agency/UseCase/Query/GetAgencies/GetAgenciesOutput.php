<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgencies;

use Businesses\Wiki\Agency\UseCase\Query\AgencyReadModel;

class GetAgenciesOutput implements GetAgenciesOutputPort
{
    /**
     * @var AgencyReadModel[]
     */
    private array $agencies = [];
    private ?int $currentPage = null;
    private ?int $lastPage = null;
    private ?int $total = null;

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
    ): void {
        $this->agencies = $agencies;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'agencies' => array_map(static fn (AgencyReadModel $agency) => $agency->toArray(), $this->agencies),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
        ];
    }
}

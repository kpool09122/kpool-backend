<?php

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgencies;

readonly class GetAgenciesInput implements GetAgenciesInputPort
{
    public function __construct(
        private int $limit,
        private string $order,
        private string $sort,
        private string $searchWords,
    ) {
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function order(): string
    {
        return $this->order;
    }

    public function sort(): string
    {
        return $this->sort;
    }

    public function searchWords(): string
    {
        return $this->searchWords;
    }
}

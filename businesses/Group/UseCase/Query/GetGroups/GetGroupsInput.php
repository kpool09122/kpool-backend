<?php

namespace Businesses\Group\UseCase\Query\GetGroups;

readonly class GetGroupsInput implements GetGroupsInputPort
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

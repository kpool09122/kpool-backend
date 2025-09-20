<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Query\GetGroups;

use Source\Wiki\Group\Application\UseCase\Query\GroupReadModel;

class GetGroupsOutput implements GetGroupsOutputPort
{
    /**
     * @var GroupReadModel[]
     */
    private array $groups = [];
    private ?int $currentPage = null;
    private ?int $lastPage = null;
    private ?int $total = null;

    /**
     * @param GroupReadModel[] $groups
     * @param int $currentPage
     * @param int $lastPage
     * @param int $total
     * @return void
     */
    public function output(
        array $groups,
        int   $currentPage,
        int   $lastPage,
        int   $total,
    ): void {
        $this->groups = $groups;
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
            'groups' => array_map(static fn (GroupReadModel $group) => $group->toArray(), $this->groups),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
        ];
    }
}

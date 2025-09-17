<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\UseCase\Query\GetMembers;

use Businesses\Wiki\Member\UseCase\Query\MemberReadModel;

class GetMembersOutput implements GetMembersOutputPort
{
    /**
     * @var MemberReadModel[]
     */
    private array $members = [];
    private ?int $currentPage = null;
    private ?int $lastPage = null;
    private ?int $total = null;

    /**
     * @param MemberReadModel[] $members
     * @param int $currentPage
     * @param int $lastPage
     * @param int $total
     * @return void
     */
    public function output(
        array $members,
        int $currentPage,
        int $lastPage,
        int $total,
    ): void {
        $this->members = $members;
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
            'members' => array_map(static fn (MemberReadModel $member) => $member->toArray(), $this->members),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
        ];
    }
}

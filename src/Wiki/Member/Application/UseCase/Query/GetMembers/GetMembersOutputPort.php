<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Query\GetMembers;

use Source\Wiki\Member\Application\UseCase\Query\MemberReadModel;

interface GetMembersOutputPort
{
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
    ): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

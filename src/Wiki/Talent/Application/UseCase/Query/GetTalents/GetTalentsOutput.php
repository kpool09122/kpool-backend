<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Query\GetTalents;

use Source\Wiki\Talent\Application\UseCase\Query\TalentReadModel;

class GetTalentsOutput implements GetTalentsOutputPort
{
    /**
     * @var TalentReadModel[]
     */
    private array $members = [];
    private ?int $currentPage = null;
    private ?int $lastPage = null;
    private ?int $total = null;

    /**
     * @param TalentReadModel[] $members
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
            'talents' => array_map(static fn (TalentReadModel $member) => $member->toArray(), $this->members),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
        ];
    }
}

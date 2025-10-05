<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Query\GetTalents;

use Source\Wiki\Talent\Application\UseCase\Query\TalentReadModel;

interface GetTalentsOutputPort
{
    /**
     * @param TalentReadModel[] $talents
     * @param int $currentPage
     * @param int $lastPage
     * @param int $total
     * @return void
     */
    public function output(
        array $talents,
        int $currentPage,
        int $lastPage,
        int $total,
    ): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Query\GetTalents;

use Source\Wiki\Talent\Application\UseCase\Query\TalentReadModel;

interface GetTalentsInterface
{
    /**
     * @param GetTalentsInputPort $input
     * @return list<TalentReadModel>
     */
    public function process(GetTalentsInputPort $input): array;
}

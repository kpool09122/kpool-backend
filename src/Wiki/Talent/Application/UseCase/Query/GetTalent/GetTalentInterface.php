<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Query\GetTalent;

use Source\Wiki\Talent\Application\UseCase\Query\TalentReadModel;

interface GetTalentInterface
{
    public function process(GetTalentInputPort $input): TalentReadModel;
}

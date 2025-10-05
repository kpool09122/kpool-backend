<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Query\GetGroup;

use Source\Wiki\Group\Application\UseCase\Query\GroupReadModel;

interface GetGroupInterface
{
    public function process(GetGroupInputPort $input): GroupReadModel;
}

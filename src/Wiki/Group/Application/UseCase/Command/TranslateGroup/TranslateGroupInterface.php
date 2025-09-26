<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;

interface TranslateGroupInterface
{
    /**
     * @param TranslateGroupInputPort $input
     * @return DraftGroup[]
     * @throws GroupNotFoundException
     */
    public function process(TranslateGroupInputPort $input): array;
}

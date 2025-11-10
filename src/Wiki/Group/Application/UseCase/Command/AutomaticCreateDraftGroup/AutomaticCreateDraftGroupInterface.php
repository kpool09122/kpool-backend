<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use Source\Wiki\Group\Domain\Entity\DraftGroup;

interface AutomaticCreateDraftGroupInterface
{
    public function process(AutomaticCreateDraftGroupInputPort $input): DraftGroup;
}

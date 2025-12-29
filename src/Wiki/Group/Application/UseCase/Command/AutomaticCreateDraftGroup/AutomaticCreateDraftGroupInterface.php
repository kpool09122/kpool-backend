<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface AutomaticCreateDraftGroupInterface
{
    /**
     * @param AutomaticCreateDraftGroupInputPort $input
     * @return DraftGroup
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutomaticCreateDraftGroupInputPort $input): DraftGroup;
}

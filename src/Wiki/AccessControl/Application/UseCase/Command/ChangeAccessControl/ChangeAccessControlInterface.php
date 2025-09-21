<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\AccessControl\Application\Exception\ActorNotFoundException;
use Source\Wiki\AccessControl\Application\Exception\UnauthorizedChangingACException;
use Source\Wiki\Shared\Domain\Entity\Actor;

interface ChangeAccessControlInterface
{
    /**
     * @param ChangeAccessControlInputPort $input
     * @return Actor
     * @throws UnauthorizedChangingACException
     * @throws ActorNotFoundException
     */
    public function process(ChangeAccessControlInputPort $input): Actor;
}

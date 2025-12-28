<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\AccessControl\Application\Exception\ActorNotFoundException;
use Source\Wiki\AccessControl\Application\Exception\UnauthorizedChangingACException;
use Source\Wiki\Principal\Domain\Entity\Principal;

interface ChangeAccessControlInterface
{
    /**
     * @param ChangeAccessControlInputPort $input
     * @return Principal
     * @throws UnauthorizedChangingACException
     * @throws ActorNotFoundException
     */
    public function process(ChangeAccessControlInputPort $input): Principal;
}

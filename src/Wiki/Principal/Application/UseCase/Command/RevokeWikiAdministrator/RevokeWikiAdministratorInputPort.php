<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator;

use Source\Shared\Domain\ValueObject\Email;

interface RevokeWikiAdministratorInputPort
{
    public function email(): Email;
}

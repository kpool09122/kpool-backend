<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator;

use Source\Shared\Domain\ValueObject\Email;

interface GrantWikiAdministratorInputPort
{
    public function email(): Email;
}

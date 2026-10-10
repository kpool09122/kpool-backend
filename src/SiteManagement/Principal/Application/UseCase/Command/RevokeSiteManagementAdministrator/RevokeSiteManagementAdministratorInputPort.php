<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator;

use Source\Shared\Domain\ValueObject\Email;

interface RevokeSiteManagementAdministratorInputPort
{
    public function email(): Email;
}

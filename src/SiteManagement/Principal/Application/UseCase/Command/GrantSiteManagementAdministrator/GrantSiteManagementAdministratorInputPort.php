<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator;

use Source\Shared\Domain\ValueObject\Email;

interface GrantSiteManagementAdministratorInputPort
{
    public function email(): Email;
}

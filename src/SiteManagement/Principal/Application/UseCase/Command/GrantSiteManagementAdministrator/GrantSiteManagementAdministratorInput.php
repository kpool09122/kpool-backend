<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator;

use Source\Shared\Domain\ValueObject\Email;

readonly class GrantSiteManagementAdministratorInput implements GrantSiteManagementAdministratorInputPort
{
    public function __construct(private Email $email)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }
}

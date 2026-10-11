<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator;

use Source\Shared\Domain\ValueObject\Email;

readonly class RevokeSiteManagementAdministratorInput implements RevokeSiteManagementAdministratorInputPort
{
    public function __construct(private Email $email)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }
}

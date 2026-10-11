<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementOperator;

use Source\Shared\Domain\ValueObject\Email;

readonly class GrantSiteManagementOperatorInput implements GrantSiteManagementOperatorInputPort
{
    public function __construct(private Email $email)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }
}

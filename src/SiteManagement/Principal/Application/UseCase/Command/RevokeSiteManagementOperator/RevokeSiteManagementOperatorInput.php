<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator;

use Source\Shared\Domain\ValueObject\Email;

readonly class RevokeSiteManagementOperatorInput implements RevokeSiteManagementOperatorInputPort
{
    public function __construct(private Email $email)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }
}

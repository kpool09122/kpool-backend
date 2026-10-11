<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementOperator;

use Source\Shared\Domain\ValueObject\Email;

interface GrantSiteManagementOperatorInputPort
{
    public function email(): Email;
}

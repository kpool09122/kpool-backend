<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator;

use Source\Shared\Domain\ValueObject\Email;

interface RevokeSiteManagementOperatorInputPort
{
    public function email(): Email;
}

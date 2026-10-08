<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class SiteManagementContext
{
    public function __construct(public PrincipalIdentifier $principalIdentifier)
    {
    }
}

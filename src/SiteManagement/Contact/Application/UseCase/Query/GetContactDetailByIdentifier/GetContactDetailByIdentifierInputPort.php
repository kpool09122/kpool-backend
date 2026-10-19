<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier;

use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface GetContactDetailByIdentifierInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function contactIdentifier(): ContactIdentifier;
}

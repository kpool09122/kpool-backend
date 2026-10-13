<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier;

use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class GetContactDetailByIdentifierInput implements GetContactDetailByIdentifierInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ContactIdentifier $contactIdentifier,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function contactIdentifier(): ContactIdentifier
    {
        return $this->contactIdentifier;
    }
}

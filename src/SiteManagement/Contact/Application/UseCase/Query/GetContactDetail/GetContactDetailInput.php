<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class GetContactDetailInput implements GetContactDetailInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private IdentityIdentifier $targetIdentityIdentifier,
        private ContactIdentifier $contactIdentifier,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function targetIdentityIdentifier(): IdentityIdentifier
    {
        return $this->targetIdentityIdentifier;
    }

    public function contactIdentifier(): ContactIdentifier
    {
        return $this->contactIdentifier;
    }
}

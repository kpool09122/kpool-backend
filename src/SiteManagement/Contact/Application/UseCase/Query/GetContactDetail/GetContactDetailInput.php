<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;

readonly class GetContactDetailInput implements GetContactDetailInputPort
{
    public function __construct(
        private IdentityIdentifier $requesterIdentityIdentifier,
        private IdentityIdentifier $targetIdentityIdentifier,
        private ContactIdentifier $contactIdentifier,
    ) {
    }

    public function requesterIdentityIdentifier(): IdentityIdentifier
    {
        return $this->requesterIdentityIdentifier;
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

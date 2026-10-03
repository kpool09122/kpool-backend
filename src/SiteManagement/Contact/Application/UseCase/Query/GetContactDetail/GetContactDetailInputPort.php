<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;

interface GetContactDetailInputPort
{
    public function requesterIdentityIdentifier(): IdentityIdentifier;

    public function targetIdentityIdentifier(): IdentityIdentifier;

    public function contactIdentifier(): ContactIdentifier;
}

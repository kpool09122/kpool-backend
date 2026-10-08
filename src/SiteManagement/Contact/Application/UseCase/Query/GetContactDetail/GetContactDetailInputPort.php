<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface GetContactDetailInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function targetPrincipalIdentifier(): PrincipalIdentifier;

    public function contactIdentifier(): ContactIdentifier;
}

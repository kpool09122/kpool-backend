<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail;

use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface GetMyContactDetailInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function contactIdentifier(): ContactIdentifier;
}

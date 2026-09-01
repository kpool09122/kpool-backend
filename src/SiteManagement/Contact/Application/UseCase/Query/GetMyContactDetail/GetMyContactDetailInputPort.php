<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;

interface GetMyContactDetailInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function contactIdentifier(): ContactIdentifier;
}

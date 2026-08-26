<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ListContactsByIdentityInputPort
{
    public function requesterIdentityIdentifier(): IdentityIdentifier;

    public function targetIdentityIdentifier(): IdentityIdentifier;
}

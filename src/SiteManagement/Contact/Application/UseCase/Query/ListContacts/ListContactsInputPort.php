<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ListContactsInputPort
{
    public function requesterIdentityIdentifier(): IdentityIdentifier;

    public function targetIdentityIdentifier(): ?IdentityIdentifier;

    public function hasReply(): ?bool;
}

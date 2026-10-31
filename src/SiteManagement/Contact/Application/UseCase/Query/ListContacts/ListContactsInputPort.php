<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ListContactsInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function targetPrincipalIdentifier(): ?PrincipalIdentifier;

    public function hasReply(): ?bool;

    public function perPage(): int;

    public function page(): int;
}

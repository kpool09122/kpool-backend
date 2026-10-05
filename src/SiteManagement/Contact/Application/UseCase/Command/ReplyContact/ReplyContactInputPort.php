<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact;

use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ReplyContactInputPort
{
    public function contactIdentifier(): ContactIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;

    public function content(): string;
}

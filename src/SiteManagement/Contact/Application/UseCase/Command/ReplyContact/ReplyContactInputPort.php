<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;

interface ReplyContactInputPort
{
    public function contactIdentifier(): ContactIdentifier;

    public function identityIdentifier(): IdentityIdentifier;

    public function content(): string;
}

<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContactByOperator;

use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ReplyContactByOperatorInputPort
{
    public function contactIdentifier(): ContactIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;

    public function content(): string;
}

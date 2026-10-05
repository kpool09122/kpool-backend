<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact;

use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class ReplyContactInput implements ReplyContactInputPort
{
    public function __construct(
        private ContactIdentifier $contactIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private string $content,
    ) {
    }

    public function contactIdentifier(): ContactIdentifier
    {
        return $this->contactIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function content(): string
    {
        return $this->content;
    }
}

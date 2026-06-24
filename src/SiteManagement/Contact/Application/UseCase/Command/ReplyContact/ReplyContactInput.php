<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;

readonly class ReplyContactInput implements ReplyContactInputPort
{
    public function __construct(
        private ContactIdentifier $contactIdentifier,
        private IdentityIdentifier $identityIdentifier,
        private string $content,
    ) {
    }

    public function contactIdentifier(): ContactIdentifier
    {
        return $this->contactIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function content(): string
    {
        return $this->content;
    }
}

<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\SiteManagement\Contact\Domain\Entity\Contact;

interface SubmitContactOutputPort
{
    public function setContact(Contact $contact): void;

    public function contact(): ?Contact;

    /**
     * @return array{
     *     contactIdentifier: ?string,
     *     identityIdentifier: ?string,
     *     category: ?int,
     *     name: ?string,
     *     email: ?string,
     *     content: ?string
     * }
     */
    public function toArray(): array;
}

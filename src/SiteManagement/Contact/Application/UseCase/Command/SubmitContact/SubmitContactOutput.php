<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\SiteManagement\Contact\Domain\Entity\Contact;

class SubmitContactOutput implements SubmitContactOutputPort
{
    private ?Contact $contact = null;

    public function setContact(Contact $contact): void
    {
        $this->contact = $contact;
    }

    public function contact(): ?Contact
    {
        return $this->contact;
    }

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
    public function toArray(): array
    {
        if ($this->contact === null) {
            return [
                'contactIdentifier' => null,
                'identityIdentifier' => null,
                'category' => null,
                'name' => null,
                'email' => null,
                'content' => null,
            ];
        }

        return [
            'contactIdentifier' => (string) $this->contact->contactIdentifier(),
            'identityIdentifier' => $this->contact->identityIdentifier() !== null
                ? (string) $this->contact->identityIdentifier()
                : null,
            'category' => $this->contact->category()->value,
            'name' => (string) $this->contact->name(),
            'email' => (string) $this->contact->email(),
            'content' => (string) $this->contact->content(),
        ];
    }
}

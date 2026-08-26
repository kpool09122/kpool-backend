<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;

class ListContactsByIdentityOutput implements ListContactsByIdentityOutputPort
{
    /** @var ContactReadModel[] */
    private array $contacts = [];

    /** @param ContactReadModel[] $contacts */
    public function output(array $contacts): void
    {
        $this->contacts = $contacts;
    }

    public function toArray(): array
    {
        return array_map(static fn (ContactReadModel $contact): array => $contact->toArray(), $this->contacts);
    }
}

<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;

class ListContactsByPrincipalOutput implements ListContactsByPrincipalOutputPort
{
    /** @var array<int, ContactReadModel> */
    private array $contacts = [];

    /** @param array<int, ContactReadModel> $contacts */
    public function output(array $contacts): void
    {
        $this->contacts = $contacts;
    }

    public function toArray(): array
    {
        return array_map(static fn (ContactReadModel $contact): array => $contact->toArray(), $this->contacts);
    }
}

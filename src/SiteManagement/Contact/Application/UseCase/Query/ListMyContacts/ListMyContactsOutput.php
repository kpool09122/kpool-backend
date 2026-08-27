<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;

class ListMyContactsOutput implements ListMyContactsOutputPort
{
    /** @var ContactReadModel[] */
    private array $contacts = [];

    /** @param ContactReadModel[] $contacts */
    public function output(array $contacts): void
    {
        $this->contacts = $contacts;
    }

    /**
     * @return array<int, array{
     *     contactIdentifier: string,
     *     identityIdentifier: ?string,
     *     category: int,
     *     name: string,
     *     replyIdentifiers: array<int, string>,
     *     createdAt: string
     * }>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (ContactReadModel $contact): array => $contact->toArray(),
            $this->contacts,
        );
    }
}

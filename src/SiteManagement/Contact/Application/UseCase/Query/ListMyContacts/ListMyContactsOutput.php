<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;

class ListMyContactsOutput implements ListMyContactsOutputPort
{
    /** @var array<int, ContactReadModel> */
    private array $contacts = [];

    /** @param array<int, ContactReadModel> $contacts */
    public function output(array $contacts): void
    {
        $this->contacts = $contacts;
    }

    /**
     * @return array<int, array{
     *     contactIdentifier: string,
     *     principalIdentifier: ?string,
     *     category: int,
     *     name: string,
     *     replyIdentifiers: array<int, string>,
     *     createdAt: string,
     *     content: string,
     *     replies: array<int, array{replyIdentifier: string, replyNumber: int, principalIdentifier: ?string, name: ?string, content: string, sentAt: ?string, createdAt: string}>
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

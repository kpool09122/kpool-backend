<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;

class ListContactsOutput implements ListContactsOutputPort
{
    /** @var ContactReadModel[] */
    private array $contacts = [];

    private int $currentPage = 1;

    private int $lastPage = 1;

    private int $total = 0;

    private int $perPage = 50;

    /** @param ContactReadModel[] $contacts */
    public function output(array $contacts, int $currentPage, int $lastPage, int $total, int $perPage): void
    {
        $this->contacts = $contacts;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    public function toArray(): array
    {
        return [
            'contacts' => array_map(static fn (ContactReadModel $contact): array => $contact->toArray(), $this->contacts),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}

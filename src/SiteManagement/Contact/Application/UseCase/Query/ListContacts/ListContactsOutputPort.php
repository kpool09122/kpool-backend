<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;

interface ListContactsOutputPort
{
    /** @param ContactReadModel[] $contacts */
    public function output(array $contacts, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /** @return array{contacts: array<array{contactIdentifier: string, identityIdentifier: ?string, category: int, name: string, replyIdentifiers: array<int, string>, createdAt: string}>, current_page: int, last_page: int, total: int, per_page: int} */
    public function toArray(): array;
}

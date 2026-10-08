<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;

interface ListContactsByPrincipalOutputPort
{
    /** @param ContactReadModel[] $contacts */
    public function output(array $contacts): void;

    /** @return array<int, array{contactIdentifier: string, principalIdentifier: ?string, category: int, name: string, replyIdentifiers: array<int, string>, createdAt: string}> */
    public function toArray(): array;
}

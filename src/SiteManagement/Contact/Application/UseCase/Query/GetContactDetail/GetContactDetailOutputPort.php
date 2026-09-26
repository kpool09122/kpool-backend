<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;

interface GetContactDetailOutputPort
{
    public function output(ContactDetailReadModel $contact): void;

    /** @return array{contactIdentifier: string, identityIdentifier: ?string, category: int, name: string, createdAt: string, content: string, replies: array<int, array{replyIdentifier: string, content: string, sentAt: string}>} */
    public function toArray(): array;
}

<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;

interface ListMyContactsOutputPort
{
    /** @param array<int, ContactReadModel> $contacts */
    public function output(array $contacts): void;

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
    public function toArray(): array;
}

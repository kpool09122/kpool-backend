<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use DateTimeInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsOutputPort;

readonly class ListMyContacts implements ListMyContactsInterface
{
    public function process(ListMyContactsInputPort $input, ListMyContactsOutputPort $output): void
    {
        $contacts = ContactModel::query()
            ->select(['id', 'identity_identifier', 'category', 'name', 'created_at'])
            ->where('identity_identifier', (string) $input->identityIdentifier())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $replyIdentifiersByContactIdentifier = ContactReplyModel::query()
            ->select(['contact_id', 'id'])
            ->whereIn('contact_id', $contacts->pluck('id'))
            ->whereNotNull('sent_at')
            ->whereNull('failed_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('contact_id')
            ->map(static fn ($replies): array => $replies->pluck('id')->all())
            ->all();

        $contacts = $contacts
            ->map(fn (ContactModel $contact): ContactReadModel => new ContactReadModel(
                contactIdentifier: (string) $contact->id,
                identityIdentifier: $contact->identity_identifier === null ? null : (string) $contact->identity_identifier,
                category: (int) $contact->category,
                name: (string) $contact->name,
                replyIdentifiers: $replyIdentifiersByContactIdentifier[(string) $contact->id] ?? [],
                createdAt: $contact->created_at->format(DateTimeInterface::ATOM),
            ))
            ->all();

        $output->output($contacts);
    }
}

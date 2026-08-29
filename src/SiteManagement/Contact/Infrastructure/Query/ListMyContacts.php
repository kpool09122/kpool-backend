<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
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
            ->select([
                'contacts.id',
                'contacts.identity_identifier',
                'contacts.category',
                'contacts.name',
                'contacts.created_at',
            ])
            ->selectRaw(
                "COALESCE(json_agg(contact_replies.id ORDER BY contact_replies.created_at, contact_replies.id) FILTER (WHERE contact_replies.sent_at IS NOT NULL AND contact_replies.failed_at IS NULL), '[]'::json) AS reply_identifiers"
            )
            ->leftJoin('contact_replies', 'contact_replies.contact_id', '=', 'contacts.id')
            ->where('contacts.identity_identifier', (string) $input->identityIdentifier())
            ->groupBy([
                'contacts.id',
                'contacts.identity_identifier',
                'contacts.category',
                'contacts.name',
                'contacts.created_at',
            ])
            ->orderByDesc('contacts.created_at')
            ->orderByDesc('contacts.id')
            ->get();

        $contacts = $contacts
            ->map(static function (ContactModel $contact): ContactReadModel {
                /** @var array<int, string> $replyIdentifiers */
                $replyIdentifiers = json_decode(
                    (string) $contact->getAttribute('reply_identifiers'),
                    true,
                    flags: JSON_THROW_ON_ERROR,
                );

                return new ContactReadModel(
                    contactIdentifier: (string) $contact->id,
                    identityIdentifier: $contact->identity_identifier === null ? null : (string) $contact->identity_identifier,
                    category: (int) $contact->category,
                    name: (string) $contact->name,
                    replyIdentifiers: $replyIdentifiers,
                    createdAt: $contact->created_at->format(DateTimeInterface::ATOM),
                );
            })
            ->all();

        $output->output($contacts);
    }
}

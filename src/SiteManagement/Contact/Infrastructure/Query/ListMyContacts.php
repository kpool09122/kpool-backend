<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
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
                'id',
                'identity_identifier',
                'category',
                'name',
                'created_at',
            ])
            ->with([
                'replies' => static function (Relation $query): void {
                    $query->select(['id', 'contact_id'])
                        ->whereNotNull('sent_at')
                        ->whereNull('failed_at')
                        ->orderBy('created_at')
                        ->orderBy('id');
                },
            ])
            ->where('identity_identifier', (string) $input->identityIdentifier())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $contacts = $contacts
            ->map(static fn (ContactModel $contact): ContactReadModel => new ContactReadModel(
                contactIdentifier: (string) $contact->id,
                identityIdentifier: $contact->identity_identifier === null ? null : (string) $contact->identity_identifier,
                category: (int) $contact->category,
                name: (string) $contact->name,
                replyIdentifiers: $contact->replies->pluck('id')->map(static fn (mixed $identifier): string => (string) $identifier)->all(),
                createdAt: $contact->created_at->format(DateTimeInterface::ATOM),
            ))
            ->all();

        $output->output($contacts);
    }
}

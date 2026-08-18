<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsOutputPort;

readonly class ListMyContacts implements ListMyContactsInterface
{
    public function __construct(
        private EncryptionServiceInterface $encryptionService,
    ) {
    }

    public function process(ListMyContactsInputPort $input, ListMyContactsOutputPort $output): void
    {
        $contacts = ContactModel::query()
            ->select(['id', 'identity_identifier', 'category', 'name', 'email', 'content'])
            ->where('identity_identifier', (string) $input->identityIdentifier())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ContactModel $contact): ContactReadModel => new ContactReadModel(
                contactIdentifier: (string) $contact->id,
                identityIdentifier: $contact->identity_identifier === null ? null : (string) $contact->identity_identifier,
                category: (int) $contact->category,
                name: (string) $contact->name,
                email: $this->encryptionService->decrypt((string) $contact->email),
                content: (string) $contact->content,
            ))
            ->all();

        $output->output($contacts);
    }
}

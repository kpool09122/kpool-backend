<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\Contact as ContactModel;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityOutputPort;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

readonly class ListContactsByIdentity implements ListContactsByIdentityInterface
{
    public function __construct(
        private EncryptionServiceInterface $encryptionService,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function process(ListContactsByIdentityInputPort $input, ListContactsByIdentityOutputPort $output): void
    {
        $requester = $this->userRepository->findByIdentityIdentifier($input->requesterIdentityIdentifier());
        if (! $requester?->isAdmin()) {
            throw new UnauthorizedException();
        }

        $contacts = ContactModel::query()
            ->select(['id', 'identity_identifier', 'category', 'name', 'email', 'content'])
            ->where('identity_identifier', (string) $input->targetIdentityIdentifier())
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

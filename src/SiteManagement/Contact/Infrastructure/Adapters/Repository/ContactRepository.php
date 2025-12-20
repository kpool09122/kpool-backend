<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use Application\Models\SiteManagement\Contact as ContactModel;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;

final class ContactRepository implements ContactRepositoryInterface
{
    public function __construct(
        private readonly EncryptionServiceInterface $encryptionService,
    ) {
    }

    public function save(Contact $contact): void
    {
        ContactModel::query()->updateOrCreate(
            [
                'id' => (string)$contact->contactIdentifier(),
            ],
            [
                'category' => $contact->category()->value,
                'name' => (string)$contact->name(),
                'email' => $this->encryptionService->encrypt((string)$contact->email()),
                'content' => (string)$contact->content(),
            ]
        );
    }
}

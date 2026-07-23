<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use Application\Models\SiteManagement\Contact as ContactModel;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;

final readonly class ContactRepository implements ContactRepositoryInterface
{
    public function __construct(
        private EncryptionServiceInterface $encryptionService,
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
                'identity_identifier' => $contact->identityIdentifier() !== null ? (string)$contact->identityIdentifier() : null,
                'name' => (string)$contact->name(),
                'email' => $this->encryptionService->encrypt((string)$contact->email()),
                'content' => (string)$contact->content(),
            ]
        );
    }

    public function findById(ContactIdentifier $contactIdentifier): ?Contact
    {
        $model = ContactModel::query()
            ->whereKey((string) $contactIdentifier)
            ->first();
        if ($model === null) {
            return null;
        }

        return new Contact(
            new ContactIdentifier((string)$model->id),
            $model->identity_identifier !== null ? new IdentityIdentifier((string)$model->identity_identifier) : null,
            Category::from((int)$model->category),
            new ContactName((string)$model->name),
            new Email($this->encryptionService->decrypt((string)$model->email)),
            new Content((string)$model->content),
        );
    }
}

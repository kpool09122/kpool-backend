<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Repository;

use Application\Models\Account\Account as AccountEloquent;
use Application\Models\Account\AccountDocument as AccountDocumentEloquent;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ContactAddress;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Phone;

class AccountRepository implements AccountRepositoryInterface
{
    public function save(Account $account): void
    {
        $addressColumns = self::contactAddressColumns($account->address());

        AccountEloquent::query()->updateOrCreate(
            ['id' => (string) $account->accountIdentifier()],
            [
                'email' => (string) $account->email(),
                'type' => $account->type()->value,
                'name' => (string) $account->name(),
                'status' => $account->status()->value,
                'category' => $account->accountCategory()->value,
                'phone' => $account->phone() !== null ? (string) $account->phone() : null,
                ...$addressColumns,
            ]
        );

        $documentRows = [];
        foreach ($account->documents()->all() as $document) {
            $documentRows[] = [
                'account_id' => (string) $account->accountIdentifier(),
                'document_type' => $document->documentType()->value,
                'document_path' => (string) $document->documentPath(),
                'uploaded_at' => $document->uploadedAt(),
            ];
        }

        AccountDocumentEloquent::query()
            ->where('account_id', (string) $account->accountIdentifier())
            ->delete();

        if ($documentRows !== []) {
            AccountDocumentEloquent::query()->insert($documentRows);
        }
    }

    public function findById(AccountIdentifier $identifier): ?Account
    {
        $eloquent = AccountEloquent::query()
            ->with('documents')
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByEmail(Email $email): ?Account
    {
        $eloquent = AccountEloquent::query()
            ->with('documents')
            ->where('email', (string) $email)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(Account $account): void
    {
        AccountEloquent::query()
            ->where('id', (string) $account->accountIdentifier())
            ->delete();
    }

    private function toDomainEntity(AccountEloquent $eloquent): Account
    {
        $documents = $eloquent->documents->map(static function ($doc): AccountDocument {
            assert($doc instanceof AccountDocumentEloquent);

            return new AccountDocument(
                new AccountIdentifier($doc->account_id),
                DocumentType::from($doc->document_type),
                new DocumentPath($doc->document_path),
                $doc->uploaded_at->toDateTimeImmutable(),
            );
        })->all();

        return new Account(
            new AccountIdentifier($eloquent->id),
            new Email($eloquent->email),
            AccountType::from($eloquent->type),
            new AccountName($eloquent->name),
            AccountStatus::from($eloquent->status),
            AccountCategory::from($eloquent->category),
            DeletionReadinessChecklist::ready(),
            new AccountDocuments($documents),
            $eloquent->phone !== null ? new Phone($eloquent->phone) : null,
            self::contactAddress($eloquent),
        );
    }

    /** @return array{address_country_code: string|null, address_administrative_area_code: string|null, address_postal_code: string|null, address_locality: string|null, address_line1: string|null, address_line2: string|null} */
    private static function contactAddressColumns(?ContactAddress $address): array
    {
        return [
            'address_country_code' => $address?->countryCode()?->value,
            'address_administrative_area_code' => $address?->administrativeAreaCode()?->code(),
            'address_postal_code' => $address?->postalCode() !== null ? (string) $address->postalCode() : null,
            'address_locality' => $address?->locality() !== null ? (string) $address->locality() : null,
            'address_line1' => $address?->addressLine1() !== null ? (string) $address->addressLine1() : null,
            'address_line2' => $address?->addressLine2() !== null ? (string) $address->addressLine2() : null,
        ];
    }

    private static function contactAddress(AccountEloquent $eloquent): ?ContactAddress
    {
        if (
            $eloquent->address_country_code === null
            && $eloquent->address_administrative_area_code === null
            && $eloquent->address_postal_code === null
            && $eloquent->address_locality === null
            && $eloquent->address_line1 === null
            && $eloquent->address_line2 === null
        ) {
            return null;
        }

        return ContactAddress::fromArray([
            'countryCode' => $eloquent->address_country_code,
            'administrativeAreaCode' => $eloquent->address_administrative_area_code,
            'postalCode' => $eloquent->address_postal_code,
            'locality' => $eloquent->address_locality,
            'addressLine1' => $eloquent->address_line1,
            'addressLine2' => $eloquent->address_line2,
        ]);
    }
}

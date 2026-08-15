<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Entity;

use Source\Account\Account\Domain\Exception\AccountDeletionBlockedException;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ContactAddress;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Phone;

class Account
{
    public function __construct(
        private readonly AccountIdentifier $accountIdentifier,
        private readonly Email $email,
        private readonly AccountType $type,
        private AccountName $name,
        private readonly AccountStatus $status,
        private AccountCategory $accountCategory,
        private readonly DeletionReadinessChecklist $deletionReadiness,
        private AccountDocuments $documents,
        private ?Phone $phone = null,
        private ?ContactAddress $address = null,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function type(): AccountType
    {
        return $this->type;
    }

    public function name(): AccountName
    {
        return $this->name;
    }

    public function changeName(AccountName $name): void
    {
        $this->name = $name;
    }

    public function phone(): ?Phone
    {
        return $this->phone;
    }

    public function changePhone(?Phone $phone): void
    {
        $this->phone = $phone;
    }

    public function address(): ?ContactAddress
    {
        return $this->address;
    }

    public function changeAddress(?ContactAddress $address): void
    {
        $this->address = $address;
    }

    public function hasRequiredContactForCategoryChange(): bool
    {
        if ($this->phone === null || $this->address === null || $this->address->countryCode() === null || $this->address->addressLine1() === null) {
            return false;
        }

        if (self::requiresAdministrativeAreaCode($this->address->countryCode())) {
            return $this->address->administrativeAreaCode() !== null;
        }

        return true;
    }

    private static function requiresAdministrativeAreaCode(CountryCode $countryCode): bool
    {
        return in_array($countryCode, [
            CountryCode::JAPAN,
            CountryCode::UNITED_STATES,
            CountryCode::KOREA_REPUBLIC,
            CountryCode::AUSTRALIA,
            CountryCode::CANADA,
            CountryCode::NEW_ZEALAND,
            CountryCode::CHINA,
            CountryCode::TAIWAN,
            CountryCode::THAILAND,
            CountryCode::PHILIPPINES,
            CountryCode::VIET_NAM,
        ], true);
    }

    public function status(): AccountStatus
    {
        return $this->status;
    }

    public function accountCategory(): AccountCategory
    {
        return $this->accountCategory;
    }

    public function setAccountCategory(AccountCategory $category): void
    {
        $this->accountCategory = $category;
    }

    public function deletionReadiness(): DeletionReadinessChecklist
    {
        return $this->deletionReadiness;
    }

    public function documents(): AccountDocuments
    {
        return $this->documents;
    }

    /**
     * @param AccountDocument[] $documents
     */
    public function replaceDocuments(array $documents): void
    {
        $this->documents->replaceWith($documents);
    }

    /**
     * @throws AccountDeletionBlockedException
     */
    public function assertDeletable(): void
    {
        $this->deletionReadiness->assertReady();
    }
}

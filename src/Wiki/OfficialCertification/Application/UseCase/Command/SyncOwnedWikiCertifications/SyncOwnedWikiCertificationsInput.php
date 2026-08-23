<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class SyncOwnedWikiCertificationsInput implements SyncOwnedWikiCertificationsInputPort
{
    /** @param TranslationSetIdentifier[] $translationSetIdentifiers */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private AccountCategory $accountCategory,
        private PrincipalIdentifier $requesterPrincipalIdentifier,
        private array $translationSetIdentifiers,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function accountCategory(): AccountCategory
    {
        return $this->accountCategory;
    }

    public function requesterPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->requesterPrincipalIdentifier;
    }

    public function translationSetIdentifiers(): array
    {
        return $this->translationSetIdentifiers;
    }
}

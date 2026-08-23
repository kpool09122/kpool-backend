<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface SyncOwnedWikiCertificationsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function accountCategory(): AccountCategory;

    public function requesterPrincipalIdentifier(): PrincipalIdentifier;

    /** @return TranslationSetIdentifier[] */
    public function translationSetIdentifiers(): array;
}

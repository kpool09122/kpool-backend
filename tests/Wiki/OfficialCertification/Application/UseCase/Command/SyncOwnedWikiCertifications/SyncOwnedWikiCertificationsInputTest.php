<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertificationsInput;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SyncOwnedWikiCertificationsInputTest extends TestCase
{
    public function testInput(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requesterPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifiers = [
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
        ];

        $input = new SyncOwnedWikiCertificationsInput(
            accountIdentifier: $accountIdentifier,
            accountCategory: AccountCategory::AGENCY,
            requesterPrincipalIdentifier: $requesterPrincipalIdentifier,
            translationSetIdentifiers: $translationSetIdentifiers,
        );

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame(AccountCategory::AGENCY, $input->accountCategory());
        $this->assertSame($requesterPrincipalIdentifier, $input->requesterPrincipalIdentifier());
        $this->assertSame($translationSetIdentifiers, $input->translationSetIdentifiers());
    }
}

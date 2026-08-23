<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications\ListMyOfficialCertificationsInput;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListMyOfficialCertificationsInputTest extends TestCase
{
    public function testInput(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $input = new ListMyOfficialCertificationsInput(
            principalIdentifier: $principalIdentifier,
            accountIdentifier: $accountIdentifier,
            accountCategory: AccountCategory::TALENT,
            status: CertificationStatus::PENDING,
            perPage: 25,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame(AccountCategory::TALENT, $input->accountCategory());
        $this->assertSame(CertificationStatus::PENDING, $input->status());
        $this->assertSame(25, $input->perPage());
    }

    public function testInputDefaultPerPage(): void
    {
        $input = new ListMyOfficialCertificationsInput(
            principalIdentifier: new PrincipalIdentifier(StrTestHelper::generateUuid()),
            accountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()),
            accountCategory: AccountCategory::AGENCY,
        );

        $this->assertSame(AccountCategory::AGENCY, $input->accountCategory());
        $this->assertNull($input->status());
        $this->assertSame(10, $input->perPage());
    }
}

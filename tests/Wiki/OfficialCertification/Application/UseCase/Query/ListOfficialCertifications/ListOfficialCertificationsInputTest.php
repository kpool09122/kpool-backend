<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications;

use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsInput;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListOfficialCertificationsInputTest extends TestCase
{
    public function testInput(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ListOfficialCertificationsInput(
            principalIdentifier: $principalIdentifier,
            status: CertificationStatus::PENDING,
            perPage: 25,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame(CertificationStatus::PENDING, $input->status());
        $this->assertSame(25, $input->perPage());
    }

    public function testInputDefaultPerPage(): void
    {
        $input = new ListOfficialCertificationsInput(
            principalIdentifier: new PrincipalIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertNull($input->status());
        $this->assertSame(10, $input->perPage());
    }
}

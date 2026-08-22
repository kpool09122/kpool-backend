<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use PHPUnit\Framework\TestCase;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationInput;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class RejectCertificationInputTest extends TestCase
{
    public function test__construct(): void
    {
        $certificationIdentifier = new CertificationIdentifier(StrTestHelper::generateUuid());
        $operatorPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new RejectCertificationInput($certificationIdentifier, $operatorPrincipalIdentifier);

        $this->assertSame($certificationIdentifier, $input->certificationIdentifier());
        $this->assertSame($operatorPrincipalIdentifier, $input->operatorPrincipalIdentifier());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\RollbackAgency;

use Source\Wiki\Agency\Application\UseCase\Command\RollbackAgency\RollbackAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackAgencyInputTest extends TestCase
{
    public function testConstruct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(3);

        $input = new RollbackAgencyInput(
            $principalIdentifier,
            $agencyIdentifier,
            $targetVersion,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($agencyIdentifier, $input->agencyIdentifier());
        $this->assertSame($targetVersion, $input->targetVersion());
    }
}

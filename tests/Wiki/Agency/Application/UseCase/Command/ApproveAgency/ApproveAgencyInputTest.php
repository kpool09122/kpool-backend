<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveAgencyInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveAgencyInput(
            $agencyIdentifier,
            $principalIdentifier,
        );

        $this->assertSame((string) $agencyIdentifier, (string) $input->agencyIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}

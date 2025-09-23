<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\RejectUpdatedAgency;

use Source\Wiki\Agency\Application\UseCase\Command\RejectUpdatedAgency\RejectUpdatedAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectUpdatedAgencyInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $input = new RejectUpdatedAgencyInput(
            $agencyIdentifier,
        );
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
    }
}

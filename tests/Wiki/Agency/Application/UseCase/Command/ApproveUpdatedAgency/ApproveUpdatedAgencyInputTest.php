<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\ApproveUpdatedAgency;

use Source\Wiki\Agency\Application\UseCase\Command\ApproveUpdatedAgency\ApproveUpdatedAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveUpdatedAgencyInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $input = new ApproveUpdatedAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
        );
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame((string)$publishedAgencyIdentifier, (string)$input->publishedAgencyIdentifier());
    }
}

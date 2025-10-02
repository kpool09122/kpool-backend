<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\SubmitAgency;

use Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency\SubmitAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitAgencyInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $input = new SubmitAgencyInput(
            $agencyIdentifier,
        );
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
    }
}

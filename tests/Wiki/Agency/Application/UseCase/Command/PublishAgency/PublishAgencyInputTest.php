<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\PublishAgency;

use Source\Wiki\Agency\Application\UseCase\Command\PublishAgency\PublishAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishAgencyInputTest extends TestCase
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
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        $input = new PublishAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $principalIdentifier,
        );
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame((string)$publishedAgencyIdentifier, (string)$input->publishedAgencyIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}

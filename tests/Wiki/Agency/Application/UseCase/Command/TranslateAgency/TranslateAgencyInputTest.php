<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateAgencyInputTest extends TestCase
{
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateAgencyInput($agencyIdentifier, $publishedAgencyIdentifier, $principalIdentifier);
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame((string)$publishedAgencyIdentifier, (string)$input->publishedAgencyIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}

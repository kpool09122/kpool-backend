<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency\AutomaticCreateDraftAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class AutomaticCreateDraftAgencyInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutomaticDraftAgencyCreationPayload(
            Language::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
        );
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutomaticCreateDraftAgencyInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency\AutomaticCreateDraftAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencySource;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class AutomaticCreateDraftAgencyInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutomaticDraftAgencyCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new AgencyName('JYP엔터테インメント'),
            new CEO('J.Y. Park'),
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('auto generated'),
            new AutomaticDraftAgencySource('webhook::draft'),
        );
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutomaticCreateDraftAgencyInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}

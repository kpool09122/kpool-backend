<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency\AutomaticCreateDraftAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencySource;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;

class AutomaticCreateDraftAgencyInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutomaticDraftAgencyCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Translation::KOREAN,
            new AgencyName('JYP엔터테インメント'),
            new CEO('J.Y. Park'),
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('auto generated'),
            new AutomaticDraftAgencySource('webhook::draft'),
        );
        $principal = new Principal(
            new PrincipalIdentifier('01F8MECHZX3TBDSZ7XRADM79XV'),
            Role::ADMINISTRATOR,
            null,
            [],
            [],
        );

        $input = new AutomaticCreateDraftAgencyInput($payload, $principal);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principal, $input->principal());
    }
}

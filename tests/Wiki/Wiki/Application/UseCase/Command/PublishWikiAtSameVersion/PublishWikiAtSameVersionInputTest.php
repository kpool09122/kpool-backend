<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion\PublishWikiAtSameVersionInput;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishWikiAtSameVersionInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::GROUP;
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];
        $talentIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];

        $visitorLocation = new VisitorLocation("JP", "01");
        $input = new PublishWikiAtSameVersionInput(
            $wikiIdentifier,
            $principalIdentifier,
            $resourceType,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
            $visitorLocation,
        );
        $this->assertSame($wikiIdentifier, $input->wikiIdentifier());
        $this->assertSame($visitorLocation, $input->visitorLocation());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($resourceType, $input->resourceType());
        $this->assertSame($agencyIdentifier, $input->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $input->groupIdentifiers());
        $this->assertSame($talentIdentifiers, $input->talentIdentifiers());
    }
}

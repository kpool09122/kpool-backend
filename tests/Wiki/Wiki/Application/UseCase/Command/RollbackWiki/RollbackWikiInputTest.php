<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\RollbackWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiInput;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackWikiInputTest extends TestCase
{
    public function testConstruct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(3);
        $resourceType = ResourceType::GROUP;
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];
        $talentIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];

        $input = new RollbackWikiInput(
            $principalIdentifier,
            $wikiIdentifier,
            $targetVersion,
            $resourceType,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($wikiIdentifier, $input->wikiIdentifier());
        $this->assertSame($targetVersion, $input->targetVersion());
        $this->assertSame($resourceType, $input->resourceType());
        $this->assertSame($agencyIdentifier, $input->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $input->groupIdentifiers());
        $this->assertSame($talentIdentifiers, $input->talentIdentifiers());
    }
}

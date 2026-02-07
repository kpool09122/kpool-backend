<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki\TranslateWikiInput;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;

        $input = new TranslateWikiInput(
            $wikiIdentifier,
            $principalIdentifier,
            $resourceType,
        );

        $this->assertSame($wikiIdentifier, $input->wikiIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($resourceType, $input->resourceType());
        $this->assertNull($input->agencyIdentifier());
        $this->assertEmpty($input->groupIdentifiers());
        $this->assertEmpty($input->talentIdentifiers());
    }

    public function testWithAllParameters(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new WikiIdentifier(StrTestHelper::generateUuid()),
        ];
        $talentIdentifiers = [
            new WikiIdentifier(StrTestHelper::generateUuid()),
        ];

        $input = new TranslateWikiInput(
            $wikiIdentifier,
            $principalIdentifier,
            $resourceType,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
        );

        $this->assertSame($wikiIdentifier, $input->wikiIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($resourceType, $input->resourceType());
        $this->assertSame($agencyIdentifier, $input->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $input->groupIdentifiers());
        $this->assertSame($talentIdentifiers, $input->talentIdentifiers());
    }
}

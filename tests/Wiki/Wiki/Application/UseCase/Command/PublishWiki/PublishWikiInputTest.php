<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\PublishWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiInput;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishWikiInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $publishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::GROUP;
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];
        $talentIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];

        $input = new PublishWikiInput(
            $wikiIdentifier,
            $publishedWikiIdentifier,
            $principalIdentifier,
            $resourceType,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
        );
        $this->assertSame((string) $wikiIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame((string) $publishedWikiIdentifier, (string) $input->publishedWikiIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($resourceType, $input->resourceType());
        $this->assertSame($agencyIdentifier, $input->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $input->groupIdentifiers());
        $this->assertSame($talentIdentifiers, $input->talentIdentifiers());
    }
}

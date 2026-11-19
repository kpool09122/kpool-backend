<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiTarget;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitWikiTargetTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::GROUP;
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];
        $talentIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];

        $input = new SubmitWikiTarget(
            $wikiIdentifier,
            $resourceType,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
        );

        $this->assertSame((string) $wikiIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame($resourceType->value, $input->resourceType()->value);
        $this->assertSame((string) $agencyIdentifier, (string) $input->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $input->groupIdentifiers());
        $this->assertSame($talentIdentifiers, $input->talentIdentifiers());
    }

    /**
     * 正常系: オプションパラメータがデフォルト値で生成されること
     *
     * @return void
     */
    public function test__constructWithDefaults(): void
    {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::GROUP;

        $input = new SubmitWikiTarget(
            $wikiIdentifier,
            $resourceType,
        );

        $this->assertSame((string) $wikiIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame($resourceType->value, $input->resourceType()->value);
        $this->assertNull($input->agencyIdentifier());
        $this->assertSame([], $input->groupIdentifiers());
        $this->assertSame([], $input->talentIdentifiers());
    }
}

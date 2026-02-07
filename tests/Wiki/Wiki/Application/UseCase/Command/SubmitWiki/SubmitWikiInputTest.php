<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiInput;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitWikiInputTest extends TestCase
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

        $input = new SubmitWikiInput(
            $wikiIdentifier,
            $principalIdentifier,
            $resourceType,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
        );

        $this->assertSame((string) $wikiIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
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
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::GROUP;

        $input = new SubmitWikiInput(
            $wikiIdentifier,
            $principalIdentifier,
            $resourceType,
        );

        $this->assertSame((string) $wikiIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($resourceType->value, $input->resourceType()->value);
        $this->assertNull($input->agencyIdentifier());
        $this->assertSame([], $input->groupIdentifiers());
        $this->assertSame([], $input->talentIdentifiers());
    }
}

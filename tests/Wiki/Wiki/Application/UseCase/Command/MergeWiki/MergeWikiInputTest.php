<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\MergeWiki;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Command\MergeWiki\MergeWikiInput;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MergeWikiInputTest extends TestCase
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
        $basic = new GroupBasic(
            name: new Name('TWICE'),
            normalizedName: 'twice',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('ONCE'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );
        $sections = new SectionContentCollection();
        $themeColor = new Color('#FF5733');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];
        $talentIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];

        $input = new MergeWikiInput(
            $wikiIdentifier,
            $basic,
            $sections,
            $themeColor,
            $principalIdentifier,
            $resourceType,
            $mergedAt,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
        );

        $this->assertSame((string) $wikiIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame($basic, $input->basic());
        $this->assertSame($sections, $input->sections());
        $this->assertSame((string) $themeColor, (string) $input->themeColor());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($resourceType->value, $input->resourceType()->value);
        $this->assertSame($mergedAt, $input->mergedAt());
        $this->assertSame((string) $agencyIdentifier, (string) $input->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $input->groupIdentifiers());
        $this->assertSame($talentIdentifiers, $input->talentIdentifiers());
    }

    /**
     * 正常系: nullable値がnullでもインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullValues(): void
    {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::GROUP;
        $basic = new GroupBasic(
            name: new Name('TWICE'),
            normalizedName: 'twice',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('ONCE'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );
        $sections = new SectionContentCollection();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $input = new MergeWikiInput(
            $wikiIdentifier,
            $basic,
            $sections,
            null,
            $principalIdentifier,
            $resourceType,
            $mergedAt,
        );

        $this->assertSame((string) $wikiIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame($basic, $input->basic());
        $this->assertSame($sections, $input->sections());
        $this->assertNull($input->themeColor());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($resourceType->value, $input->resourceType()->value);
        $this->assertSame($mergedAt, $input->mergedAt());
        $this->assertNull($input->agencyIdentifier());
        $this->assertSame([], $input->groupIdentifiers());
        $this->assertSame([], $input->talentIdentifiers());
    }
}

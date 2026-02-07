<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\CreateWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiInput;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateWikiInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $publishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
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
        $slug = new Slug('twice');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];
        $talentIdentifiers = [new WikiIdentifier(StrTestHelper::generateUuid())];

        $input = new CreateWikiInput(
            $publishedWikiIdentifier,
            $language,
            $resourceType,
            $basic,
            $sections,
            $themeColor,
            $slug,
            $principalIdentifier,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
        );

        $this->assertSame((string) $publishedWikiIdentifier, (string) $input->publishedWikiIdentifier());
        $this->assertSame($language->value, $input->language()->value);
        $this->assertSame($resourceType->value, $input->resourceType()->value);
        $this->assertSame($basic, $input->basic());
        $this->assertSame($sections, $input->sections());
        $this->assertSame((string) $themeColor, (string) $input->themeColor());
        $this->assertSame((string) $slug, (string) $input->slug());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame((string) $agencyIdentifier, (string) $input->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $input->groupIdentifiers());
        $this->assertSame($talentIdentifiers, $input->talentIdentifiers());
    }
}

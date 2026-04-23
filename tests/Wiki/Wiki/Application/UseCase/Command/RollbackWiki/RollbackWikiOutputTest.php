<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\RollbackWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackWikiOutputTest extends TestCase
{
    /**
     * 正常系: Wikiがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    public function testToArrayWithWikis(): void
    {
        $wiki1 = new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('gr-twice'),
            Language::KOREAN,
            ResourceType::GROUP,
            new GroupBasic(
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
            ),
            new SectionContentCollection(),
            null,
            new Version(3),
        );

        $wiki2 = new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('gr-twice-ja'),
            Language::JAPANESE,
            ResourceType::GROUP,
            new GroupBasic(
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
            ),
            new SectionContentCollection(),
            null,
            new Version(3),
        );

        $output = new RollbackWikiOutput();
        $output->setWikis([$wiki1, $wiki2]);

        $result = $output->toArray();

        $this->assertCount(2, $result['wikis']);
        $this->assertSame('ko', $result['wikis'][0]['language']);
        $this->assertSame('TWICE', $result['wikis'][0]['name']);
        $this->assertSame('group', $result['wikis'][0]['resourceType']);
        $this->assertSame(3, $result['wikis'][0]['version']);
        $this->assertSame('ja', $result['wikis'][1]['language']);
    }

    /**
     * 正常系: Wikiがセットされていない場合、toArrayが空の配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutWikis(): void
    {
        $output = new RollbackWikiOutput();

        $result = $output->toArray();

        $this->assertSame(['wikis' => []], $result);
    }
}

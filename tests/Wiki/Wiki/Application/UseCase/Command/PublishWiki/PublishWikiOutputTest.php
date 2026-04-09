<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\PublishWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiOutput;
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

class PublishWikiOutputTest extends TestCase
{
    /**
     * 正常系: Wikiがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    public function testToArrayWithWiki(): void
    {
        $wiki = new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('twice'),
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

        $output = new PublishWikiOutput();
        $output->setWiki($wiki);

        $result = $output->toArray();

        $this->assertSame('ko', $result['language']);
        $this->assertSame('TWICE', $result['name']);
        $this->assertSame('group', $result['resourceType']);
        $this->assertSame(3, $result['version']);
    }

    /**
     * 正常系: Wikiがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutWiki(): void
    {
        $output = new PublishWikiOutput();

        $result = $output->toArray();

        $this->assertSame([
            'language' => null,
            'name' => null,
            'resourceType' => null,
            'version' => null,
        ], $result);
    }
}

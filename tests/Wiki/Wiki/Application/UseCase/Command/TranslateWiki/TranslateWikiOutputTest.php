<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki\TranslateWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateWikiOutputTest extends TestCase
{
    /**
     * 正常系: DraftWikiがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    public function testToArrayWithDraftWikis(): void
    {
        $draftWiki1 = new DraftWiki(
            new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('twice'),
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
            ApprovalStatus::Pending,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
        );

        $draftWiki2 = new DraftWiki(
            new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('twice'),
            Language::ENGLISH,
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
            ApprovalStatus::Pending,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
        );

        $output = new TranslateWikiOutput();
        $output->setDraftWikis([$draftWiki1, $draftWiki2]);

        $result = $output->toArray();

        $this->assertCount(2, $result['draftWikis']);
        $this->assertSame('ja', $result['draftWikis'][0]['language']);
        $this->assertSame('TWICE', $result['draftWikis'][0]['name']);
        $this->assertSame('group', $result['draftWikis'][0]['resourceType']);
        $this->assertSame('pending', $result['draftWikis'][0]['status']);
        $this->assertSame('en', $result['draftWikis'][1]['language']);
    }

    /**
     * 正常系: DraftWikiがセットされていない場合、toArrayが空の配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutDraftWikis(): void
    {
        $output = new TranslateWikiOutput();

        $result = $output->toArray();

        $this->assertSame(['draftWikis' => []], $result);
    }
}

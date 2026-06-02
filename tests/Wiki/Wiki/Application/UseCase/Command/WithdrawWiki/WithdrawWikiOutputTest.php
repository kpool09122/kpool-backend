<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki\WithdrawWikiOutput;
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

class WithdrawWikiOutputTest extends TestCase
{
    public function testToArrayWithDraftWiki(): void
    {
        $draftWiki = new DraftWiki(
            new DraftWikiIdentifier(StrTestHelper::generateUuid()),
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
            ),
            new SectionContentCollection(),
            null,
            ApprovalStatus::Pending,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
        );

        $output = new WithdrawWikiOutput();
        $output->setDraftWiki($draftWiki);

        $this->assertSame([
            'language' => 'ko',
            'name' => 'TWICE',
            'resourceType' => 'group',
            'status' => 'pending',
        ], $output->toArray());
    }

    public function testToArrayWithoutDraftWiki(): void
    {
        $output = new WithdrawWikiOutput();

        $this->assertSame([
            'language' => null,
            'name' => null,
            'resourceType' => null,
            'status' => null,
        ], $output->toArray());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\CreateDraftTalent;
use Tests\Helper\CreateGroup;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftTalentRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの下書きタレント情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUuid();
        $publishedId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translation = Language::KOREAN;
        $name = '창빈';
        $realName = '서창빈';
        $agencyId = StrTestHelper::generateUuid();
        $groupIdentifiers = [StrTestHelper::generateUuid()];
        $birthday = '1999-08-13';
        $career = 'Stray Kids main rapper and producer. Member of 3RACHA.';
        $relevantVideoLinks = ['https://www.youtube.com/watch?v=EaswWiwMVs8'];
        $status = ApprovalStatus::Pending;

        // 先にグループを作成
        foreach ($groupIdentifiers as $groupId) {
            CreateGroup::create($groupId);
        }

        CreateDraftTalent::create($id, [
            'published_id' => $publishedId,
            'translation_set_identifier' => $translationSetId,
            'editor_id' => $editorId,
            'language' => $translation->value,
            'name' => $name,
            'real_name' => $realName,
            'agency_id' => $agencyId,
            'group_identifiers' => $groupIdentifiers,
            'birthday' => $birthday,
            'career' => $career,
            'relevant_video_links' => $relevantVideoLinks,
            'status' => $status->value,
        ]);

        $repository = $this->app->make(DraftTalentRepositoryInterface::class);
        $draft = $repository->findById(new TalentIdentifier($id));

        $this->assertInstanceOf(DraftTalent::class, $draft);
        $this->assertSame($id, (string) $draft->talentIdentifier());
        $this->assertSame($publishedId, (string) $draft->publishedTalentIdentifier());
        $this->assertSame($translationSetId, (string) $draft->translationSetIdentifier());
        $this->assertSame($editorId, (string) $draft->editorIdentifier());
        $this->assertSame($translation, $draft->language());
        $this->assertSame($name, (string) $draft->name());
        $this->assertSame($realName, (string) $draft->realName());
        $this->assertSame($agencyId, (string) $draft->agencyIdentifier());
        $this->assertSame($groupIdentifiers, array_map(
            static fn (GroupIdentifier $identifier): string => (string) $identifier,
            $draft->groupIdentifiers(),
        ));
        $this->assertInstanceOf(Birthday::class, $draft->birthday());
        $this->assertInstanceOf(DateTimeImmutable::class, $draft->birthday()->value());
        $this->assertSame($birthday, $draft->birthday()->format('Y-m-d'));
        $this->assertSame($career, (string) $draft->career());
        $this->assertSame($relevantVideoLinks, $draft->relevantVideoLinks()->toStringArray());
        $this->assertSame($status, $draft->status());
    }

    /**
     * 正常系：下書きの誕生日が未設定の場合はnullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenBirthdayIsNull(): void
    {
        $id = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);
        CreateDraftTalent::create($id, [
            'language' => Language::KOREAN->value,
            'name' => '현진',
            'real_name' => '황현진',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [$groupId],
            'birthday' => null,
            'career' => 'Stray Kids main dancer and lead rapper.',
        ]);

        $repository = $this->app->make(DraftTalentRepositoryInterface::class);
        $draft = $repository->findById(new TalentIdentifier($id));

        $this->assertInstanceOf(DraftTalent::class, $draft);
        $this->assertNull($draft->birthday());
    }

    /**
     * 正常系：存在しないIDの下書きタレント情報を検索した場合、nullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNoDraftTalent(): void
    {
        $repository = $this->app->make(DraftTalentRepositoryInterface::class);
        $draft = $repository->findById(new TalentIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($draft);
    }

    /**
     * 正常系：下書きタレント情報を保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $groupId = StrTestHelper::generateUuid();

        // 先にグループを作成
        CreateGroup::create($groupId);

        $draft = new DraftTalent(
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new TalentName('필릭스'),
            new RealName('이용복'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new GroupIdentifier($groupId)],
            new Birthday(new DateTimeImmutable('2000-09-15')),
            new Career('Stray Kids lead dancer and sub-rapper. Known for his deep voice.'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://www.youtube.com/watch?v=EaswWiwMVs8'),
            ]),
            ApprovalStatus::UnderReview,
        );

        $repository = $this->app->make(DraftTalentRepositoryInterface::class);
        $repository->save($draft);

        $this->assertDatabaseHas('draft_talents', [
            'id' => (string) $draft->talentIdentifier(),
            'published_id' => (string) $draft->publishedTalentIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'language' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'real_name' => (string) $draft->realName(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'birthday' => $draft->birthday()?->format('Y-m-d'),
            'career' => (string) $draft->career(),
            'status' => $draft->status()->value,
        ]);

        // 中間テーブルの確認
        $this->assertDatabaseHas('draft_talent_group', [
            'draft_talent_id' => (string) $draft->talentIdentifier(),
            'group_id' => $groupId,
        ]);

        $rawVideos = DB::table('draft_talents')
            ->where('id', (string) $draft->talentIdentifier())
            ->value('relevant_video_links');

        $decodedVideos = json_decode((string) $rawVideos, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            $draft->relevantVideoLinks()->toStringArray(),
            $decodedVideos,
        );
    }

    /**
     * 正常系：下書きタレント情報を削除できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $id = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);

        $draft = new DraftTalent(
            new TalentIdentifier($id),
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new TalentName('승민'),
            new RealName('김승민'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new GroupIdentifier($groupId)],
            new Birthday(new DateTimeImmutable('2000-09-22')),
            new Career('Stray Kids lead vocalist.'),
            new RelevantVideoLinks([]),
            ApprovalStatus::Pending,
        );

        CreateDraftTalent::create($id, [
            'published_id' => (string) $draft->publishedTalentIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'language' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'real_name' => (string) $draft->realName(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'group_identifiers' => array_map(
                static fn (GroupIdentifier $identifier): string => (string) $identifier,
                $draft->groupIdentifiers(),
            ),
            'birthday' => $draft->birthday()?->format('Y-m-d'),
            'career' => (string) $draft->career(),
            'status' => $draft->status()->value,
        ]);

        $repository = $this->app->make(DraftTalentRepositoryInterface::class);
        $repository->delete($draft);

        $this->assertDatabaseMissing('draft_talents', [
            'id' => $id,
        ]);

        $this->assertDatabaseMissing('draft_talent_group', [
            'draft_talent_id' => $id,
            'group_id' => $groupId,
        ]);
    }

    /**
     * 正常系：翻訳セットIDに紐づく下書きタレント情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSet(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $groupId1 = StrTestHelper::generateUuid();
        $groupId2 = StrTestHelper::generateUuid();
        $groupId3 = StrTestHelper::generateUuid();

        CreateGroup::create($groupId1);
        CreateGroup::create($groupId2);
        CreateGroup::create($groupId3);

        $draft1Id = StrTestHelper::generateUuid();
        $draft1 = [
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::KOREAN->value,
            'name' => '아이엔',
            'real_name' => '양정인',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [$groupId1],
            'birthday' => '2001-02-08',
            'career' => 'Stray Kids youngest member (maknae) and vocalist.',
            'status' => ApprovalStatus::Pending->value,
        ];

        $draft2Id = StrTestHelper::generateUuid();
        $draft2 = [
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::JAPANESE->value,
            'name' => 'アイエン',
            'real_name' => 'ヤン・ジョンイン',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [$groupId2],
            'birthday' => '2001-02-08',
            'career' => 'Stray Kidsの末っ子でボーカル担当。',
            'status' => ApprovalStatus::Approved->value,
        ];

        $otherDraftId = StrTestHelper::generateUuid();
        $otherDraft = [
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'language' => Language::ENGLISH->value,
            'name' => 'Karina',
            'real_name' => 'Yu Ji-min',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [$groupId3],
            'birthday' => '2000-04-11',
            'career' => 'aespa leader, main dancer, lead vocalist, and center.',
            'status' => ApprovalStatus::Pending->value,
        ];

        CreateDraftTalent::create($draft1Id, $draft1);
        CreateDraftTalent::create($draft2Id, $draft2);
        CreateDraftTalent::create($otherDraftId, $otherDraft);

        $repository = $this->app->make(DraftTalentRepositoryInterface::class);
        $drafts = $repository->findByTranslationSet($translationSetIdentifier);

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftTalent $draft): string => (string) $draft->talentIdentifier(), $drafts);
        $this->assertContains($draft1Id, $draftIds);
        $this->assertContains($draft2Id, $draftIds);
        $this->assertNotContains($otherDraftId, $draftIds);

        $birthdayMap = [];
        foreach ($drafts as $draft) {
            $birthdayMap[(string) $draft->talentIdentifier()] = $draft->birthday()?->format('Y-m-d');
        }

        $this->assertSame('2001-02-08', $birthdayMap[$draft1Id]);
        $this->assertSame('2001-02-08', $birthdayMap[$draft2Id]);
    }

    /**
     * 正常系：該当する下書きタレントが存在しない場合、空配列が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetWhenNoDrafts(): void
    {
        $repository = $this->app->make(DraftTalentRepositoryInterface::class);
        $drafts = $repository->findByTranslationSet(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }
}

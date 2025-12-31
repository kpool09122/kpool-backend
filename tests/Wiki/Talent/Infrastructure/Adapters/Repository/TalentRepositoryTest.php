<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\CreateDraftTalent;
use Tests\Helper\CreateTalent;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDのタレント情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();
        $translation = Language::KOREAN;
        $name = '방찬';
        $realName = '크리스토퍼 방';
        $agencyId = StrTestHelper::generateUuid();
        $groupIdentifiers = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        $birthday = '1997-10-03';
        $career = 'Stray Kids leader, producer, and rapper. Member of 3RACHA.';
        $imageLink = '/images/talents/bangchan.jpg';
        $relevantVideoLinks = ['https://www.youtube.com/watch?v=EaswWiwMVs8', 'https://www.youtube.com/watch?v=dcNRbbQBJUE'];
        $version = 3;

        CreateTalent::create($id, [
            'translation_set_identifier' => $translationSetId,
            'language' => $translation->value,
            'name' => $name,
            'real_name' => $realName,
            'agency_id' => $agencyId,
            'group_identifiers' => $groupIdentifiers,
            'birthday' => $birthday,
            'career' => $career,
            'image_link' => $imageLink,
            'relevant_video_links' => $relevantVideoLinks,
            'version' => $version,
        ]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findById(new TalentIdentifier($id));

        $this->assertInstanceOf(Talent::class, $talent);
        $this->assertSame($id, (string) $talent->talentIdentifier());
        $this->assertSame($translationSetId, (string) $talent->translationSetIdentifier());
        $this->assertSame($translation, $talent->language());
        $this->assertSame($name, (string) $talent->name());
        $this->assertSame($realName, (string) $talent->realName());
        $this->assertSame($agencyId, (string) $talent->agencyIdentifier());
        $this->assertSame($groupIdentifiers, array_map(
            static fn (GroupIdentifier $identifier): string => (string) $identifier,
            $talent->groupIdentifiers(),
        ));
        $this->assertInstanceOf(Birthday::class, $talent->birthday());
        $this->assertSame($birthday, $talent->birthday()->format('Y-m-d'));
        $this->assertSame($career, (string) $talent->career());
        $this->assertSame($imageLink, (string) $talent->imageLink());
        $this->assertSame($relevantVideoLinks, $talent->relevantVideoLinks()->toStringArray());
        $this->assertSame($version, $talent->version()->value());
    }

    /**
     * 正常系：誕生日が未設定の場合はnullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenBirthdayIsNull(): void
    {
        $id = StrTestHelper::generateUuid();

        CreateTalent::create($id, [
            'language' => Language::KOREAN->value,
            'name' => '리노',
            'real_name' => '이민호',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [StrTestHelper::generateUuid()],
            'birthday' => null,
            'career' => 'Stray Kids main dancer and sub-vocalist.',
            'image_link' => '/images/talents/leeknow.jpg',
        ]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findById(new TalentIdentifier($id));

        $this->assertInstanceOf(Talent::class, $talent);
        $this->assertNull($talent->birthday());
    }

    /**
     * 正常系：存在しないIDを指定した場合、nullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNoTalent(): void
    {
        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findById(new TalentIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($talent);
    }

    /**
     * 正常系：指定したIDの下書きタレント情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDraftById(): void
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
        $imageLink = '/images/talents/changbin.jpg';
        $relevantVideoLinks = ['https://www.youtube.com/watch?v=EaswWiwMVs8'];
        $status = ApprovalStatus::Pending;

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
            'image_link' => $imageLink,
            'relevant_video_links' => $relevantVideoLinks,
            'status' => $status->value,
        ]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $draft = $repository->findDraftById(new TalentIdentifier($id));

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
        $this->assertSame($imageLink, (string) $draft->imageLink());
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
    public function testFindDraftByIdWhenBirthdayIsNull(): void
    {
        $id = StrTestHelper::generateUuid();

        CreateDraftTalent::create($id, [
            'language' => Language::KOREAN->value,
            'name' => '현진',
            'real_name' => '황현진',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [StrTestHelper::generateUuid()],
            'birthday' => null,
            'career' => 'Stray Kids main dancer and lead rapper.',
            'image_link' => '/images/talents/hyunjin.jpg',
        ]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $draft = $repository->findDraftById(new TalentIdentifier($id));

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
    public function testFindDraftByIdWhenNoDraftTalent(): void
    {
        $repository = $this->app->make(TalentRepositoryInterface::class);
        $draft = $repository->findDraftById(new TalentIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($draft);
    }

    /**
     * 正常系：タレント情報を保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $talent = new Talent(
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new TalentName('한'),
            new RealName('지성'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new GroupIdentifier(StrTestHelper::generateUuid()),
                new GroupIdentifier(StrTestHelper::generateUuid()),
            ],
            new Birthday(new DateTimeImmutable('2000-09-14')),
            new Career('Stray Kids lead vocalist and main rapper. Member of 3RACHA.'),
            new ImagePath('/images/talents/han.jpg'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://www.youtube.com/watch?v=EaswWiwMVs8'),
                new ExternalContentLink('https://www.youtube.com/watch?v=dcNRbbQBJUE'),
            ]),
            new Version(4),
        );

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $repository->save($talent);

        $expectedGroups = array_map(
            static fn (GroupIdentifier $identifier): string => (string) $identifier,
            $talent->groupIdentifiers(),
        );

        $this->assertDatabaseHas('talents', [
            'id' => (string) $talent->talentIdentifier(),
            'translation_set_identifier' => (string) $talent->translationSetIdentifier(),
            'language' => $talent->language()->value,
            'name' => (string) $talent->name(),
            'real_name' => (string) $talent->realName(),
            'agency_id' => (string) $talent->agencyIdentifier(),
            'birthday' => $talent->birthday()?->format('Y-m-d'),
            'career' => (string) $talent->career(),
            'image_link' => (string) $talent->imageLink(),
            'version' => $talent->version()->value(),
        ]);

        $rawGroups = DB::table('talents')
            ->where('id', (string) $talent->talentIdentifier())
            ->value('group_identifiers');
        $rawVideos = DB::table('talents')
            ->where('id', (string) $talent->talentIdentifier())
            ->value('relevant_video_links');

        $decodedGroups = json_decode((string) $rawGroups, true, 512, JSON_THROW_ON_ERROR);
        $decodedVideos = json_decode((string) $rawVideos, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($expectedGroups, $decodedGroups);
        $this->assertSame(
            $talent->relevantVideoLinks()->toStringArray(),
            $decodedVideos,
        );
    }

    /**
     * 正常系：下書きタレント情報を保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testSaveDraft(): void
    {
        $draft = new DraftTalent(
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new TalentName('필릭스'),
            new RealName('이용복'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new GroupIdentifier(StrTestHelper::generateUuid())],
            new Birthday(new DateTimeImmutable('2000-09-15')),
            new Career('Stray Kids lead dancer and sub-rapper. Known for his deep voice.'),
            new ImagePath('/images/talents/felix.jpg'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://www.youtube.com/watch?v=EaswWiwMVs8'),
            ]),
            ApprovalStatus::UnderReview,
        );

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $repository->saveDraft($draft);

        $expectedGroups = array_map(
            static fn (GroupIdentifier $identifier): string => (string) $identifier,
            $draft->groupIdentifiers(),
        );

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
            'image_link' => (string) $draft->imageLink(),
            'status' => $draft->status()->value,
        ]);

        $rawGroups = DB::table('draft_talents')
            ->where('id', (string) $draft->talentIdentifier())
            ->value('group_identifiers');
        $rawVideos = DB::table('draft_talents')
            ->where('id', (string) $draft->talentIdentifier())
            ->value('relevant_video_links');

        $decodedGroups = json_decode((string) $rawGroups, true, 512, JSON_THROW_ON_ERROR);
        $decodedVideos = json_decode((string) $rawVideos, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($expectedGroups, $decodedGroups);
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
    public function testDeleteDraft(): void
    {
        $id = StrTestHelper::generateUuid();
        $draft = new DraftTalent(
            new TalentIdentifier($id),
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new TalentName('승민'),
            new RealName('김승민'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new GroupIdentifier(StrTestHelper::generateUuid())],
            new Birthday(new DateTimeImmutable('2000-09-22')),
            new Career('Stray Kids lead vocalist.'),
            null,
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

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $repository->deleteDraft($draft);

        $this->assertDatabaseMissing('draft_talents', [
            'id' => $id,
        ]);
    }

    /**
     * 正常系：翻訳セットIDに紐づく下書きタレント情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDraftsByTranslationSet(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $draft1Id = StrTestHelper::generateUuid();
        $draft1 = [
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::KOREAN->value,
            'name' => '아이엔',
            'real_name' => '양정인',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [StrTestHelper::generateUuid()],
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
            'group_identifiers' => [StrTestHelper::generateUuid()],
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
            'group_identifiers' => [StrTestHelper::generateUuid()],
            'birthday' => '2000-04-11',
            'career' => 'aespa leader, main dancer, lead vocalist, and center.',
            'status' => ApprovalStatus::Pending->value,
        ];

        CreateDraftTalent::create($draft1Id, $draft1);
        CreateDraftTalent::create($draft2Id, $draft2);
        CreateDraftTalent::create($otherDraftId, $otherDraft);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet($translationSetIdentifier);

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
    public function testFindDraftsByTranslationSetWhenNoDrafts(): void
    {
        $repository = $this->app->make(TalentRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }
}

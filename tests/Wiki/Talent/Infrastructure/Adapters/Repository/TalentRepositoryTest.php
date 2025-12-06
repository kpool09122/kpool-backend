<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
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
use Source\Wiki\Talent\Infrastracture\Adapters\Repository\TalentRepository;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class TalentRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDのタレント情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUlid();
        $translationSetId = StrTestHelper::generateUlid();
        $translation = Language::JAPANESE;
        $name = 'タレント';
        $realName = '本名タレント';
        $agencyId = StrTestHelper::generateUlid();
        $groupIdentifiers = [StrTestHelper::generateUlid(), StrTestHelper::generateUlid()];
        $birthday = '1990-01-01';
        $career = '経歴サンプル';
        $imageLink = '/images/talent.png';
        $relevantVideoLinks = ['https://example.com/video1', 'https://example.com/video2'];
        $version = 3;

        DB::table('talents')->upsert([
            'id' => $id,
            'translation_set_identifier' => $translationSetId,
            'language' => $translation->value,
            'name' => $name,
            'real_name' => $realName,
            'agency_id' => $agencyId,
            'group_identifiers' => json_encode($groupIdentifiers, JSON_THROW_ON_ERROR),
            'birthday' => $birthday,
            'career' => $career,
            'image_link' => $imageLink,
            'relevant_video_links' => json_encode($relevantVideoLinks, JSON_THROW_ON_ERROR),
            'version' => $version,
        ], 'id');

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
        $this->assertInstanceOf(DateTimeImmutable::class, $talent->birthday()->value());
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
    public function testFindByIdWhenBirthdayIsNull(): void
    {
        $id = StrTestHelper::generateUlid();

        DB::table('talents')->upsert([
            'id' => $id,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => Language::JAPANESE->value,
            'name' => 'タレント',
            'real_name' => '本名タレント',
            'agency_id' => StrTestHelper::generateUlid(),
            'group_identifiers' => json_encode([StrTestHelper::generateUlid()], JSON_THROW_ON_ERROR),
            'birthday' => null,
            'career' => '経歴サンプル',
            'image_link' => '/images/talent.png',
            'relevant_video_links' => json_encode([], JSON_THROW_ON_ERROR),
            'version' => 1,
        ], 'id');

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
    public function testFindByIdWhenNoTalent(): void
    {
        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findById(new TalentIdentifier(StrTestHelper::generateUlid()));

        $this->assertNull($talent);
    }

    /**
     * 正常系：指定したIDの下書きタレント情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindDraftById(): void
    {
        $id = StrTestHelper::generateUlid();
        $publishedId = StrTestHelper::generateUlid();
        $translationSetId = StrTestHelper::generateUlid();
        $editorId = StrTestHelper::generateUlid();
        $translation = Language::ENGLISH;
        $name = 'Talent Draft';
        $realName = 'Real Name';
        $agencyId = StrTestHelper::generateUlid();
        $groupIdentifiers = [StrTestHelper::generateUlid()];
        $birthday = '1992-02-02';
        $career = 'Draft Career';
        $imageLink = '/images/draft.png';
        $relevantVideoLinks = ['https://example.com/draft-video'];
        $status = ApprovalStatus::Pending;

        DB::table('draft_talents')->upsert([
            'id' => $id,
            'published_id' => $publishedId,
            'translation_set_identifier' => $translationSetId,
            'editor_id' => $editorId,
            'language' => $translation->value,
            'name' => $name,
            'real_name' => $realName,
            'agency_id' => $agencyId,
            'group_identifiers' => json_encode($groupIdentifiers, JSON_THROW_ON_ERROR),
            'birthday' => $birthday,
            'career' => $career,
            'image_link' => $imageLink,
            'relevant_video_links' => json_encode($relevantVideoLinks, JSON_THROW_ON_ERROR),
            'status' => $status->value,
        ], 'id');

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
    public function testFindDraftByIdWhenBirthdayIsNull(): void
    {
        $id = StrTestHelper::generateUlid();

        DB::table('draft_talents')->upsert([
            'id' => $id,
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'editor_id' => StrTestHelper::generateUlid(),
            'language' => Language::ENGLISH->value,
            'name' => 'Draft Talent',
            'real_name' => 'Draft Real Name',
            'agency_id' => StrTestHelper::generateUlid(),
            'group_identifiers' => json_encode([StrTestHelper::generateUlid()], JSON_THROW_ON_ERROR),
            'birthday' => null,
            'career' => 'Draft Career',
            'image_link' => '/images/draft.png',
            'relevant_video_links' => json_encode([], JSON_THROW_ON_ERROR),
            'status' => ApprovalStatus::Pending->value,
        ], 'id');

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
    public function testFindDraftByIdWhenNoDraftTalent(): void
    {
        $repository = $this->app->make(TalentRepositoryInterface::class);
        $draft = $repository->findDraftById(new TalentIdentifier(StrTestHelper::generateUlid()));

        $this->assertNull($draft);
    }

    /**
     * 正常系：タレント情報を保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    public function testSave(): void
    {
        $talent = new Talent(
            new TalentIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new TalentName('채영'),
            new RealName('손채영'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [
                new GroupIdentifier(StrTestHelper::generateUlid()),
                new GroupIdentifier(StrTestHelper::generateUlid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('TWICEメンバー'),
            new ImagePath('/images/chaeyoung.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.com/video1'),
                new ExternalContentLink('https://example.com/video2'),
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
    public function testSaveDraft(): void
    {
        $draft = new DraftTalent(
            new TalentIdentifier(StrTestHelper::generateUlid()),
            new TalentIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::ENGLISH,
            new TalentName('Chaeyoung'),
            new RealName('Son Chaeyoung'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [new GroupIdentifier(StrTestHelper::generateUlid())],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('TWICE member'),
            new ImagePath('/images/draft.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.com/draft'),
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
    public function testDeleteDraft(): void
    {
        $id = StrTestHelper::generateUlid();
        $draft = new DraftTalent(
            new TalentIdentifier($id),
            new TalentIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new TalentName('삭제용タレント'),
            new RealName('삭제本名'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [new GroupIdentifier(StrTestHelper::generateUlid())],
            new Birthday(new DateTimeImmutable('1995-05-05')),
            new Career('削除予定'),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Pending,
        );

        DB::table('draft_talents')->insert([
            'id' => $id,
            'published_id' => (string) $draft->publishedTalentIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'language' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'real_name' => (string) $draft->realName(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'group_identifiers' => json_encode(
                array_map(
                    static fn (GroupIdentifier $identifier): string => (string) $identifier,
                    $draft->groupIdentifiers(),
                ),
                JSON_THROW_ON_ERROR,
            ),
            'birthday' => $draft->birthday()?->format('Y-m-d'),
            'career' => (string) $draft->career(),
            'relevant_video_links' => json_encode([], JSON_THROW_ON_ERROR),
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
    public function testFindDraftsByTranslationSet(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());

        $draft1 = [
            'id' => StrTestHelper::generateUlid(),
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUlid(),
            'language' => Language::KOREAN->value,
            'name' => '드래프트1',
            'real_name' => '본명1',
            'agency_id' => StrTestHelper::generateUlid(),
            'group_identifiers' => json_encode([StrTestHelper::generateUlid()], JSON_THROW_ON_ERROR),
            'birthday' => '1991-06-01',
            'career' => '커리어1',
            'status' => ApprovalStatus::Pending->value,
        ];

        $draft2 = [
            'id' => StrTestHelper::generateUlid(),
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUlid(),
            'language' => Language::JAPANESE->value,
            'name' => 'ドラフト2',
            'real_name' => '本名2',
            'agency_id' => StrTestHelper::generateUlid(),
            'group_identifiers' => json_encode([StrTestHelper::generateUlid()], JSON_THROW_ON_ERROR),
            'birthday' => '1992-07-02',
            'career' => 'キャリア2',
            'status' => ApprovalStatus::Approved->value,
        ];

        $otherDraft = [
            'id' => StrTestHelper::generateUlid(),
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'editor_id' => StrTestHelper::generateUlid(),
            'language' => Language::ENGLISH->value,
            'name' => 'Other',
            'real_name' => 'Other Real',
            'agency_id' => StrTestHelper::generateUlid(),
            'group_identifiers' => json_encode([StrTestHelper::generateUlid()], JSON_THROW_ON_ERROR),
            'birthday' => '1999-09-09',
            'career' => 'Other career',
            'status' => ApprovalStatus::Pending->value,
        ];

        DB::table('draft_talents')->insert([$draft1, $draft2, $otherDraft]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet($translationSetIdentifier);

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftTalent $draft): string => (string) $draft->talentIdentifier(), $drafts);
        $this->assertContains($draft1['id'], $draftIds);
        $this->assertContains($draft2['id'], $draftIds);
        $this->assertNotContains($otherDraft['id'], $draftIds);

        $birthdayMap = [];
        foreach ($drafts as $draft) {
            $birthdayMap[(string) $draft->talentIdentifier()] = $draft->birthday()?->format('Y-m-d');
        }

        $this->assertSame('1991-06-01', $birthdayMap[$draft1['id']]);
        $this->assertSame('1992-07-02', $birthdayMap[$draft2['id']]);
    }

    /**
     * 正常系：該当する下書きタレントが存在しない場合、空配列が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindDraftsByTranslationSetWhenNoDrafts(): void
    {
        $repository = $this->app->make(TalentRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet(
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }

    /**
     * 正常系：DateTimeInterface 実装（Carbon）を渡した場合でも DateTimeImmutable に変換されること.
     */
    public function testCreateBirthdayConvertsMutableDateTimeInstance(): void
    {
        $repository = new TalentRepository();
        $reflection = new ReflectionClass($repository);
        $method = $reflection->getMethod('createBirthday');
        $method->setAccessible(true);

        $carbonBirthday = Carbon::parse('1999-12-31');

        /** @var Birthday $birthday */
        $birthday = $method->invoke($repository, $carbonBirthday);

        $this->assertInstanceOf(Birthday::class, $birthday);
        $this->assertInstanceOf(DateTimeImmutable::class, $birthday->value());
        $this->assertSame('1999-12-31', $birthday->format('Y-m-d'));
    }

    /**
     * 正常系：DateTimeImmutable を渡した場合は同一インスタンスが利用されること.
     */
    public function testCreateBirthdayKeepsImmutableInstance(): void
    {
        $repository = new TalentRepository();
        $reflection = new ReflectionClass($repository);
        $method = $reflection->getMethod('createBirthday');
        $method->setAccessible(true);

        $immutableBirthday = new DateTimeImmutable('1988-01-01');

        /** @var Birthday $birthday */
        $birthday = $method->invoke($repository, $immutableBirthday);

        $this->assertInstanceOf(Birthday::class, $birthday);
        $this->assertSame($immutableBirthday, $birthday->value());
    }
}

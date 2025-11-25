<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Adapters\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[PHPUnitGroup('useDb')]
class GroupRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDのグループ情報が取得できること.
     * @throws BindingResolutionException
     */
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUlid();
        $translationSetId = StrTestHelper::generateUlid();
        $translation = Language::KOREAN;
        $name = 'Stray Kids';
        $agencyId = StrTestHelper::generateUlid();
        $description = 'K-pop boy group.';
        $songIds = [StrTestHelper::generateUlid(), StrTestHelper::generateUlid()];
        $imagePath = '/images/groups/skz.png';
        $version = 3;

        DB::table('groups')->upsert([
            'id' => $id,
            'translation_set_identifier' => $translationSetId,
            'translation' => $translation->value,
            'name' => $name,
            'agency_id' => $agencyId,
            'description' => $description,
            'song_identifiers' => json_encode($songIds),
            'image_path' => $imagePath,
            'version' => $version,
        ], 'id');

        /** @var GroupRepositoryInterface $repository */
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findById(new GroupIdentifier($id));

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame($id, (string) $group->groupIdentifier());
        $this->assertSame($translationSetId, (string) $group->translationSetIdentifier());
        $this->assertSame($translation, $group->language());
        $this->assertSame($name, (string) $group->name());
        $this->assertSame($agencyId, (string) $group->agencyIdentifier());
        $this->assertSame($description, (string) $group->description());
        $this->assertSame($imagePath, (string) $group->imagePath());
        $this->assertSame($version, $group->version()->value());
        $this->assertSame(
            $songIds,
            array_map(static fn (SongIdentifier $identifier): string => (string) $identifier, $group->songIdentifiers()),
        );
    }

    /**
     * 正常系：指定したIDのグループ情報が存在しない場合、nullが返却されること.
     * @throws BindingResolutionException
     */
    public function testFindByIdWhenNotExist(): void
    {
        /** @var GroupRepositoryInterface $repository */
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findById(new GroupIdentifier(StrTestHelper::generateUlid()));

        $this->assertNull($group);
    }

    /**
     * 正常系：指定したIDの下書きグループ情報が取得できること.
     * @throws BindingResolutionException
     */
    public function testFindDraftById(): void
    {
        $id = StrTestHelper::generateUlid();
        $publishedId = StrTestHelper::generateUlid();
        $translationSetId = StrTestHelper::generateUlid();
        $editorId = StrTestHelper::generateUlid();
        $translation = Language::ENGLISH;
        $name = 'Stray Kids EN Draft';
        $agencyId = StrTestHelper::generateUlid();
        $description = 'English draft';
        $songIds = [StrTestHelper::generateUlid()];
        $imagePath = '/images/groups/skz-en.png';
        $status = ApprovalStatus::Pending;

        DB::table('draft_groups')->upsert([
            'id' => $id,
            'published_id' => $publishedId,
            'translation_set_identifier' => $translationSetId,
            'editor_id' => $editorId,
            'translation' => $translation->value,
            'name' => $name,
            'agency_id' => $agencyId,
            'description' => $description,
            'song_identifiers' => json_encode($songIds),
            'image_path' => $imagePath,
            'status' => $status->value,
        ], 'id');

        /** @var GroupRepositoryInterface $repository */
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findDraftById(new GroupIdentifier($id));

        $this->assertInstanceOf(DraftGroup::class, $group);
        $this->assertSame($id, (string) $group->groupIdentifier());
        $this->assertSame($publishedId, (string) $group->publishedGroupIdentifier());
        $this->assertSame($editorId, (string) $group->editorIdentifier());
        $this->assertSame($translationSetId, (string) $group->translationSetIdentifier());
        $this->assertSame($translation, $group->language());
        $this->assertSame($name, (string) $group->name());
        $this->assertSame($agencyId, (string) $group->agencyIdentifier());
        $this->assertSame($description, (string) $group->description());
        $this->assertSame($status, $group->status());
        $this->assertSame(
            $songIds,
            array_map(static fn (SongIdentifier $identifier): string => (string) $identifier, $group->songIdentifiers()),
        );
        $this->assertSame($imagePath, (string) $group->imagePath());
    }

    /**
     * 正常系：指定したIDの下書きグループ情報が存在しない場合、nullが返却されること.
     * @throws BindingResolutionException
     */
    public function testFindDraftByIdWhenNotExist(): void
    {
        /** @var GroupRepositoryInterface $repository */
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findDraftById(new GroupIdentifier(StrTestHelper::generateUlid()));

        $this->assertNull($group);
    }

    /**
     * 正常系：正しくグループ情報を保存できること.
     * @throws BindingResolutionException
     */
    public function testSave(): void
    {
        $group = new Group(
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::JAPANESE,
            new GroupName('TWICE'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            new Description('Girl group'),
            [
                new SongIdentifier(StrTestHelper::generateUlid()),
                new SongIdentifier(StrTestHelper::generateUlid()),
            ],
            new ImagePath('/images/groups/twice.png'),
            new Version(5),
        );

        /** @var GroupRepositoryInterface $repository */
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $repository->save($group);

        $this->assertDatabaseHas('groups', [
            'id' => (string) $group->groupIdentifier(),
            'translation' => $group->language()->value,
            'name' => (string) $group->name(),
            'agency_id' => (string) $group->agencyIdentifier(),
            'description' => (string) $group->description(),
            'image_path' => (string) $group->imagePath(),
            'version' => $group->version()->value(),
        ]);
    }

    /**
     * 正常系：正しく下書きを保存できること.
     * @throws BindingResolutionException
     */
    public function testSaveDraft(): void
    {
        $draft = new DraftGroup(
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::ENGLISH,
            new GroupName('NEWJEANS'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            new Description('New draft'),
            [new SongIdentifier(StrTestHelper::generateUlid())],
            new ImagePath('/images/groups/nj.png'),
            ApprovalStatus::UnderReview,
        );

        /** @var GroupRepositoryInterface $repository */
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $repository->saveDraft($draft);

        $this->assertDatabaseHas('draft_groups', [
            'id' => (string) $draft->groupIdentifier(),
            'published_id' => (string) $draft->publishedGroupIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'translation' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'description' => (string) $draft->description(),
            'status' => $draft->status()->value,
        ]);
    }

    /**
     * 正常系：正しく下書を削除できること.
     * @throws BindingResolutionException
     */
    public function testDeleteDraft(): void
    {
        $id = StrTestHelper::generateUlid();
        $draft = new DraftGroup(
            new GroupIdentifier($id),
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::JAPANESE,
            new GroupName('削除対象'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            new Description('Delete me'),
            [new SongIdentifier(StrTestHelper::generateUlid())],
            null,
            ApprovalStatus::Pending,
        );

        DB::table('draft_groups')->insert([
            'id' => $id,
            'published_id' => (string) $draft->publishedGroupIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'translation' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'description' => (string) $draft->description(),
            'song_identifiers' => json_encode([(string) $draft->songIdentifiers()[0]]),
            'status' => $draft->status()->value,
        ]);

        /** @var GroupRepositoryInterface $repository */
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $repository->deleteDraft($draft);

        $this->assertDatabaseMissing('draft_groups', [
            'id' => $id,
        ]);
    }

    /**
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
            'translation' => Language::KOREAN->value,
            'name' => '드래프트1',
            'agency_id' => StrTestHelper::generateUlid(),
            'description' => '첫번째',
            'song_identifiers' => json_encode([StrTestHelper::generateUlid()]),
            'status' => ApprovalStatus::Pending->value,
        ];

        $draft2 = [
            'id' => StrTestHelper::generateUlid(),
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUlid(),
            'translation' => Language::JAPANESE->value,
            'name' => 'ドラフト2',
            'agency_id' => StrTestHelper::generateUlid(),
            'description' => '二件目',
            'song_identifiers' => json_encode([StrTestHelper::generateUlid()]),
            'status' => ApprovalStatus::Approved->value,
        ];

        $otherDraft = [
            'id' => StrTestHelper::generateUlid(),
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'editor_id' => StrTestHelper::generateUlid(),
            'translation' => Language::ENGLISH->value,
            'name' => 'Other',
            'agency_id' => StrTestHelper::generateUlid(),
            'description' => 'Other set',
            'song_identifiers' => json_encode([StrTestHelper::generateUlid()]),
            'status' => ApprovalStatus::Pending->value,
        ];

        DB::table('draft_groups')->insert([$draft1, $draft2, $otherDraft]);

        /** @var GroupRepositoryInterface $repository */
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet($translationSetIdentifier);

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftGroup $draft): string => (string) $draft->groupIdentifier(), $drafts);
        $this->assertContains($draft1['id'], $draftIds);
        $this->assertContains($draft2['id'], $draftIds);
        $this->assertNotContains($otherDraft['id'], $draftIds);
    }

    /**
     * 正常系：指定した翻訳セットIDの下書き情報が存在しない場合、空配列が返却されること.
     * @throws BindingResolutionException
     */
    public function testFindDraftsByTranslationSetWhenNotExist(): void
    {
        /** @var GroupRepositoryInterface $repository */
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet(
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }
}

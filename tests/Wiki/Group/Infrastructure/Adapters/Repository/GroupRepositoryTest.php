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
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
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
        $id = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();
        $translation = Language::KOREAN;
        $name = 'Stray Kids';
        $normalizedName = 'stray kids';
        $agencyId = StrTestHelper::generateUuid();
        $description = 'K-pop boy group.';
        $songIds = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        $imagePath = '/images/groups/skz.png';
        $version = 3;

        DB::table('groups')->upsert([
            'id' => $id,
            'translation_set_identifier' => $translationSetId,
            'translation' => $translation->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'agency_id' => $agencyId,
            'description' => $description,
            'song_identifiers' => json_encode($songIds),
            'image_path' => $imagePath,
            'version' => $version,
        ], 'id');

        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findById(new GroupIdentifier($id));

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame($id, (string) $group->groupIdentifier());
        $this->assertSame($translationSetId, (string) $group->translationSetIdentifier());
        $this->assertSame($translation, $group->language());
        $this->assertSame($name, (string) $group->name());
        $this->assertSame($normalizedName, $group->normalizedName());
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
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findById(new GroupIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($group);
    }

    /**
     * 正常系：指定したIDの下書きグループ情報が取得できること.
     * @throws BindingResolutionException
     */
    public function testFindDraftById(): void
    {
        $id = StrTestHelper::generateUuid();
        $publishedId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translation = Language::ENGLISH;
        $name = 'Stray Kids EN Draft';
        $normalizedName = 'stray kids en draft';
        $agencyId = StrTestHelper::generateUuid();
        $description = 'English draft';
        $songIds = [StrTestHelper::generateUuid()];
        $imagePath = '/images/groups/skz-en.png';
        $status = ApprovalStatus::Pending;

        DB::table('draft_groups')->upsert([
            'id' => $id,
            'published_id' => $publishedId,
            'translation_set_identifier' => $translationSetId,
            'editor_id' => $editorId,
            'translation' => $translation->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'agency_id' => $agencyId,
            'description' => $description,
            'song_identifiers' => json_encode($songIds),
            'image_path' => $imagePath,
            'status' => $status->value,
        ], 'id');

        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findDraftById(new GroupIdentifier($id));

        $this->assertInstanceOf(DraftGroup::class, $group);
        $this->assertSame($id, (string) $group->groupIdentifier());
        $this->assertSame($publishedId, (string) $group->publishedGroupIdentifier());
        $this->assertSame($editorId, (string) $group->editorIdentifier());
        $this->assertSame($translationSetId, (string) $group->translationSetIdentifier());
        $this->assertSame($translation, $group->language());
        $this->assertSame($name, (string) $group->name());
        $this->assertSame($normalizedName, $group->normalizedName());
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
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findDraftById(new GroupIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($group);
    }

    /**
     * 正常系：正しくグループ情報を保存できること.
     * @throws BindingResolutionException
     */
    public function testSave(): void
    {
        $group = new Group(
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('Girl group'),
            [
                new SongIdentifier(StrTestHelper::generateUuid()),
                new SongIdentifier(StrTestHelper::generateUuid()),
            ],
            new ImagePath('/images/groups/twice.png'),
            new Version(5),
        );

        $repository = $this->app->make(GroupRepositoryInterface::class);
        $repository->save($group);

        $this->assertDatabaseHas('groups', [
            'id' => (string) $group->groupIdentifier(),
            'translation' => $group->language()->value,
            'name' => (string) $group->name(),
            'normalized_name' => $group->normalizedName(),
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
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::ENGLISH,
            new GroupName('NEWJEANS'),
            'newjeans',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('New draft'),
            [new SongIdentifier(StrTestHelper::generateUuid())],
            new ImagePath('/images/groups/nj.png'),
            ApprovalStatus::UnderReview,
        );

        $repository = $this->app->make(GroupRepositoryInterface::class);
        $repository->saveDraft($draft);

        $this->assertDatabaseHas('draft_groups', [
            'id' => (string) $draft->groupIdentifier(),
            'published_id' => (string) $draft->publishedGroupIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'translation' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'normalized_name' => $draft->normalizedName(),
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
        $id = StrTestHelper::generateUuid();
        $draft = new DraftGroup(
            new GroupIdentifier($id),
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new GroupName('削除対象'),
            'さくじょたいしょう',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('Delete me'),
            [new SongIdentifier(StrTestHelper::generateUuid())],
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
            'normalized_name' => $draft->normalizedName(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'description' => (string) $draft->description(),
            'song_identifiers' => json_encode([(string) $draft->songIdentifiers()[0]]),
            'status' => $draft->status()->value,
        ]);
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $repository->deleteDraft($draft);

        $this->assertDatabaseMissing('draft_groups', [
            'id' => $id,
        ]);
    }

    /**
     * 正常系：指定した翻訳セットIDの下書き情報が取得できること.
     * @throws BindingResolutionException
     */
    public function testFindDraftsByTranslationSet(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $draft1 = [
            'id' => StrTestHelper::generateUuid(),
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'translation' => Language::KOREAN->value,
            'name' => '드래프트1',
            'normalized_name' => 'ㄷㄹㅍㅌ1',
            'agency_id' => StrTestHelper::generateUuid(),
            'description' => '첫번째',
            'song_identifiers' => json_encode([StrTestHelper::generateUuid()]),
            'status' => ApprovalStatus::Pending->value,
        ];

        $draft2 = [
            'id' => StrTestHelper::generateUuid(),
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'translation' => Language::JAPANESE->value,
            'name' => 'ドラフト2',
            'normalized_name' => 'どらふと2',
            'agency_id' => StrTestHelper::generateUuid(),
            'description' => '二件目',
            'song_identifiers' => json_encode([StrTestHelper::generateUuid()]),
            'status' => ApprovalStatus::Approved->value,
        ];

        $otherDraft = [
            'id' => StrTestHelper::generateUuid(),
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'editor_id' => StrTestHelper::generateUuid(),
            'translation' => Language::ENGLISH->value,
            'name' => 'Other',
            'normalized_name' => 'other',
            'agency_id' => StrTestHelper::generateUuid(),
            'description' => 'Other set',
            'song_identifiers' => json_encode([StrTestHelper::generateUuid()]),
            'status' => ApprovalStatus::Pending->value,
        ];

        DB::table('draft_groups')->insert([$draft1, $draft2, $otherDraft]);

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
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }
}

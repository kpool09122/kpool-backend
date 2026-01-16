<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Adapters\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftGroupRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの下書きグループ情報が取得できること.
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
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
            'status' => $status->value,
        ], 'id');

        $repository = $this->app->make(DraftGroupRepositoryInterface::class);
        $group = $repository->findById(new GroupIdentifier($id));

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
    }

    /**
     * 正常系：指定したIDの下書きグループ情報が存在しない場合、nullが返却されること.
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(DraftGroupRepositoryInterface::class);
        $group = $repository->findById(new GroupIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($group);
    }

    /**
     * 正常系：正しく下書きを保存できること.
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
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
            ApprovalStatus::UnderReview,
        );

        $repository = $this->app->make(DraftGroupRepositoryInterface::class);
        $repository->save($draft);

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
    #[Group('useDb')]
    public function testDelete(): void
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
            'status' => $draft->status()->value,
        ]);

        $repository = $this->app->make(DraftGroupRepositoryInterface::class);
        $repository->delete($draft);

        $this->assertDatabaseMissing('draft_groups', [
            'id' => $id,
        ]);
    }

    /**
     * 正常系：指定した翻訳セットIDの下書き情報が取得できること.
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSet(): void
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
            'status' => ApprovalStatus::Pending->value,
        ];

        DB::table('draft_groups')->insert([$draft1, $draft2, $otherDraft]);

        $repository = $this->app->make(DraftGroupRepositoryInterface::class);
        $drafts = $repository->findByTranslationSet($translationSetIdentifier);

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
    #[Group('useDb')]
    public function testFindByTranslationSetWhenNotExist(): void
    {
        $repository = $this->app->make(DraftGroupRepositoryInterface::class);
        $drafts = $repository->findByTranslationSet(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }
}

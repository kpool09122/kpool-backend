<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Adapters\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GroupRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDのグループ情報が取得できること.
     * @throws BindingResolutionException
     */
    #[PHPUnitGroup('useDb')]
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();
        $translation = Language::KOREAN;
        $name = 'Stray Kids';
        $normalizedName = 'stray kids';
        $agencyId = StrTestHelper::generateUuid();
        $description = 'K-pop boy group.';
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
    }

    /**
     * 正常系：指定したIDのグループ情報が存在しない場合、nullが返却されること.
     * @throws BindingResolutionException
     */
    #[PHPUnitGroup('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findById(new GroupIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($group);
    }

    /**
     * 正常系：正しくグループ情報を保存できること.
     * @throws BindingResolutionException
     */
    #[PHPUnitGroup('useDb')]
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
     * 正常系：TranslationSetIdentifierでグループ情報が取得できること.
     * @throws BindingResolutionException
     */
    #[PHPUnitGroup('useDb')]
    public function testFindByTranslationSetIdentifier(): void
    {
        $translationSetId = StrTestHelper::generateUuid();
        $id1 = StrTestHelper::generateUuid();
        $id2 = StrTestHelper::generateUuid();
        $otherTranslationSetId = StrTestHelper::generateUuid();
        $id3 = StrTestHelper::generateUuid();

        // 同じtranslation_set_identifierを持つグループを2つ作成
        DB::table('groups')->upsert([
            'id' => $id1,
            'translation_set_identifier' => $translationSetId,
            'translation' => Language::KOREAN->value,
            'name' => 'TWICE KO',
            'normalized_name' => 'twice ko',
            'agency_id' => StrTestHelper::generateUuid(),
            'description' => 'K-pop girl group.',
            'image_path' => '/images/groups/twice-ko.png',
            'version' => 3,
        ], 'id');

        DB::table('groups')->upsert([
            'id' => $id2,
            'translation_set_identifier' => $translationSetId,
            'translation' => Language::JAPANESE->value,
            'name' => 'TWICE JA',
            'normalized_name' => 'twice ja',
            'agency_id' => StrTestHelper::generateUuid(),
            'description' => 'K-pop girl group.',
            'image_path' => '/images/groups/twice-ja.png',
            'version' => 3,
        ], 'id');

        // 別のtranslation_set_identifierを持つグループ
        DB::table('groups')->upsert([
            'id' => $id3,
            'translation_set_identifier' => $otherTranslationSetId,
            'translation' => Language::KOREAN->value,
            'name' => 'aespa',
            'normalized_name' => 'aespa',
            'agency_id' => StrTestHelper::generateUuid(),
            'description' => 'K-pop girl group.',
            'image_path' => '/images/groups/aespa.png',
            'version' => 1,
        ], 'id');

        $repository = $this->app->make(GroupRepositoryInterface::class);
        $groups = $repository->findByTranslationSetIdentifier(new TranslationSetIdentifier($translationSetId));

        $this->assertCount(2, $groups);
        $ids = array_map(fn (Group $g) => (string) $g->groupIdentifier(), $groups);
        $this->assertContains($id1, $ids);
        $this->assertContains($id2, $ids);
        $this->assertNotContains($id3, $ids);
    }

    /**
     * 正常系：TranslationSetIdentifierでグループが存在しない場合、空配列が返却されること.
     * @throws BindingResolutionException
     */
    #[PHPUnitGroup('useDb')]
    public function testFindByTranslationSetIdentifierWhenNotExist(): void
    {
        $repository = $this->app->make(GroupRepositoryInterface::class);
        $groups = $repository->findByTranslationSetIdentifier(new TranslationSetIdentifier(StrTestHelper::generateUuid()));

        $this->assertIsArray($groups);
        $this->assertEmpty($groups);
    }
}

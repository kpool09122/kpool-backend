<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Adapters\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\CreateGroup;
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
        $version = 3;

        CreateGroup::create($id, [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'stray-kids',
            'translation' => $translation->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'agency_id' => $agencyId,
            'description' => $description,
            'version' => $version,
        ]);

        $repository = $this->app->make(GroupRepositoryInterface::class);
        $group = $repository->findById(new GroupIdentifier($id));

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame($id, (string) $group->groupIdentifier());
        $this->assertSame($translationSetId, (string) $group->translationSetIdentifier());
        $this->assertSame('stray-kids', (string) $group->slug());
        $this->assertSame($translation, $group->language());
        $this->assertSame($name, (string) $group->name());
        $this->assertSame($normalizedName, $group->normalizedName());
        $this->assertSame($agencyId, (string) $group->agencyIdentifier());
        $this->assertSame($description, (string) $group->description());
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
            new Slug('twice'),
            Language::JAPANESE,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('Girl group'),
            new Version(5),
        );

        $repository = $this->app->make(GroupRepositoryInterface::class);
        $repository->save($group);

        $this->assertDatabaseHas('groups', [
            'id' => (string) $group->groupIdentifier(),
            'slug' => (string) $group->slug(),
            'translation' => $group->language()->value,
            'name' => (string) $group->name(),
            'normalized_name' => $group->normalizedName(),
            'agency_id' => (string) $group->agencyIdentifier(),
            'description' => (string) $group->description(),
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
        CreateGroup::create($id1, [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'twice-ko',
            'translation' => Language::KOREAN->value,
            'name' => 'TWICE KO',
            'normalized_name' => 'twice ko',
            'description' => 'K-pop girl group.',
            'version' => 3,
        ]);

        CreateGroup::create($id2, [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'twice-ja',
            'translation' => Language::JAPANESE->value,
            'name' => 'TWICE JA',
            'normalized_name' => 'twice ja',
            'description' => 'K-pop girl group.',
            'version' => 3,
        ]);

        // 別のtranslation_set_identifierを持つグループ
        CreateGroup::create($id3, [
            'translation_set_identifier' => $otherTranslationSetId,
            'slug' => 'aespa',
            'translation' => Language::KOREAN->value,
            'name' => 'aespa',
            'normalized_name' => 'aespa',
            'description' => 'K-pop girl group.',
            'version' => 1,
        ]);

        $repository = $this->app->make(GroupRepositoryInterface::class);
        $groups = $repository->findByTranslationSetIdentifier(new TranslationSetIdentifier($translationSetId));

        $this->assertCount(2, $groups);
        $ids = array_map(static fn (Group $g) => (string) $g->groupIdentifier(), $groups);
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

    /**
     * 正常系：指定したSlugのAgencyが存在する場合、trueが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[PHPUnitGroup('useDb')]
    public function testExistsBySlug(): void
    {
        $slug = 'twice';
        $id = StrTestHelper::generateUuid();

        CreateGroup::create($id, [
            'slug' => $slug,
        ]);

        $groupRepository = $this->app->make(GroupRepositoryInterface::class);
        $exists = $groupRepository->existsBySlug(new Slug($slug));

        $this->assertTrue($exists);
    }

    /**
     * 正常系：指定したSlugのAgencyが存在しない場合、falseが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[PHPUnitGroup('useDb')]
    public function testExistsBySlugWhenNoAgency(): void
    {
        $groupRepository = $this->app->make(GroupRepositoryInterface::class);
        $exists = $groupRepository->existsBySlug(new Slug('non-existent-slug'));

        $this->assertFalse($exists);
    }
}

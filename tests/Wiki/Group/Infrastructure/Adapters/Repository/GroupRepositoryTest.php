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
}

<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\Description as AgencyDescription;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier as GroupAgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description as GroupDescription;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Image\Infrastructure\Service\ImageAuthorizationResourceBuilder;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier as SongAgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier as TalentAgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier as TalentGroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\FoundedIn;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ImageAuthorizationResourceBuilderTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $this->mockAllRepositories();

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $this->assertInstanceOf(ImageAuthorizationResourceBuilder::class, $builder);
    }

    /**
     * 正常系: DraftAgency から Resource が構築されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithAgency(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $resourceIdentifier = new ResourceIdentifier($agencyId);
        $draftAgency = $this->createDraftAgency($agencyId);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftAgency);

        $this->mockAllRepositoriesExcept(['draftAgency']);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::AGENCY, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($agencyId, $resource->agencyId());
        $this->assertSame([], $resource->groupIds());
        $this->assertSame([], $resource->talentIds());
    }

    /**
     * 異常系: DraftAgency が見つからない場合、空の Resource が返されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithAgencyNotFound(): void
    {
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->mockAllRepositoriesExcept(['draftAgency']);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::AGENCY, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
    }

    /**
     * 正常系: DraftGroup から Resource が構築されること（agencyId あり）
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithGroup(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $resourceIdentifier = new ResourceIdentifier($groupId);
        $draftGroup = $this->createDraftGroup($groupId, $agencyId);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftGroup);

        $this->mockAllRepositoriesExcept(['draftGroup']);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::GROUP, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($agencyId, $resource->agencyId());
        $this->assertSame([$groupId], $resource->groupIds());
        $this->assertSame([], $resource->talentIds());
    }

    /**
     * 正常系: DraftGroup から Resource が構築されること（agencyId なし）
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithGroupWithoutAgency(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $resourceIdentifier = new ResourceIdentifier($groupId);
        $draftGroup = $this->createDraftGroup($groupId, null);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftGroup);

        $this->mockAllRepositoriesExcept(['draftGroup']);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::GROUP, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([$groupId], $resource->groupIds());
    }

    /**
     * 異常系: DraftGroup が見つからない場合、空の Resource が返されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithGroupNotFound(): void
    {
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->mockAllRepositoriesExcept(['draftGroup']);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::GROUP, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([], $resource->groupIds());
    }

    /**
     * 正常系: DraftTalent から Resource が構築されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithTalent(): void
    {
        $talentId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $groupId1 = StrTestHelper::generateUuid();
        $groupId2 = StrTestHelper::generateUuid();
        $resourceIdentifier = new ResourceIdentifier($talentId);
        $draftTalent = $this->createDraftTalent($talentId, $agencyId, [$groupId1, $groupId2]);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftTalent);

        $this->mockAllRepositoriesExcept(['draftTalent']);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::TALENT, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($agencyId, $resource->agencyId());
        $this->assertSame([$groupId1, $groupId2], $resource->groupIds());
        $this->assertSame([$talentId], $resource->talentIds());
    }

    /**
     * 正常系: DraftTalent から Resource が構築されること（オプショナルフィールドなし）
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithTalentWithoutOptionalFields(): void
    {
        $talentId = StrTestHelper::generateUuid();
        $resourceIdentifier = new ResourceIdentifier($talentId);
        $draftTalent = $this->createDraftTalent($talentId, null, []);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftTalent);

        $this->mockAllRepositoriesExcept(['draftTalent']);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::TALENT, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([], $resource->groupIds());
        $this->assertSame([$talentId], $resource->talentIds());
    }

    /**
     * 異常系: DraftTalent が見つからない場合、空の Resource が返されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithTalentNotFound(): void
    {
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->mockAllRepositoriesExcept(['draftTalent']);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::TALENT, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame([], $resource->talentIds());
    }

    /**
     * 正常系: DraftSong から Resource が構築されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithSong(): void
    {
        $songId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $resourceIdentifier = new ResourceIdentifier($songId);
        $draftSong = $this->createDraftSong($songId, $agencyId, $groupId, $talentId);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftSong);

        $this->mockAllRepositoriesExcept(['draftSong']);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::SONG, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($agencyId, $resource->agencyId());
        $this->assertSame([$groupId], $resource->groupIds());
        $this->assertSame([$talentId], $resource->talentIds());
    }

    /**
     * 正常系: DraftSong から Resource が構築されること（オプショナルフィールドなし）
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithSongWithoutOptionalFields(): void
    {
        $songId = StrTestHelper::generateUuid();
        $resourceIdentifier = new ResourceIdentifier($songId);
        $draftSong = $this->createDraftSong($songId, null, null, null);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftSong);

        $this->mockAllRepositoriesExcept(['draftSong']);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::SONG, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([], $resource->groupIds());
        $this->assertSame([], $resource->talentIds());
    }

    /**
     * 異常系: DraftSong が見つからない場合、空の Resource が返されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithSongNotFound(): void
    {
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->mockAllRepositoriesExcept(['draftSong']);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::SONG, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
    }

    /**
     * 正常系: ResourceType::IMAGE の場合、空の Resource が返されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithImage(): void
    {
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());

        $this->mockAllRepositories();

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::IMAGE, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([], $resource->groupIds());
        $this->assertSame([], $resource->talentIds());
    }

    /**
     * 正常系: buildFromDraftImage が DraftImage から Resource を構築すること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftImage(): void
    {
        $talentId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $draftImage = $this->createDraftImage(ResourceType::TALENT, $talentId);
        $draftTalent = $this->createDraftTalent($talentId, $agencyId, []);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftTalent);

        $this->mockAllRepositoriesExcept(['draftTalent']);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftImage($draftImage);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($agencyId, $resource->agencyId());
        $this->assertSame([$talentId], $resource->talentIds());
    }

    /**
     * 正常系: buildFromImage が Published Agency から Resource を構築すること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromImageWithAgency(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $image = $this->createImage(ResourceType::AGENCY, $agencyId);
        $agency = $this->createAgency($agencyId);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->andReturn($agency);

        $this->mockAllRepositoriesExcept(['agency']);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($agencyId, $resource->agencyId());
    }

    /**
     * 正常系: buildFromImage が Published Group から Resource を構築すること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromImageWithGroup(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $image = $this->createImage(ResourceType::GROUP, $groupId);
        $group = $this->createGroup($groupId, $agencyId);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->andReturn($group);

        $this->mockAllRepositoriesExcept(['group']);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($agencyId, $resource->agencyId());
        $this->assertSame([$groupId], $resource->groupIds());
    }

    /**
     * 正常系: buildFromImage が Published Talent から Resource を構築すること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromImageWithTalent(): void
    {
        $talentId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $image = $this->createImage(ResourceType::TALENT, $talentId);
        $talent = $this->createTalent($talentId, $agencyId, [$groupId]);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($talent);

        $this->mockAllRepositoriesExcept(['talent']);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($agencyId, $resource->agencyId());
        $this->assertSame([$groupId], $resource->groupIds());
        $this->assertSame([$talentId], $resource->talentIds());
    }

    /**
     * 正常系: buildFromImage が Published Song から Resource を構築すること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromImageWithSong(): void
    {
        $songId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $image = $this->createImage(ResourceType::SONG, $songId);
        $song = $this->createSong($songId, $agencyId, $groupId, $talentId);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->andReturn($song);

        $this->mockAllRepositoriesExcept(['song']);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($agencyId, $resource->agencyId());
        $this->assertSame([$groupId], $resource->groupIds());
        $this->assertSame([$talentId], $resource->talentIds());
    }

    /**
     * 異常系: Published Agency が見つからない場合、空の Resource が返されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromImageWithAgencyNotFound(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $image = $this->createImage(ResourceType::AGENCY, $agencyId);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->mockAllRepositoriesExcept(['agency']);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
    }

    /**
     * 異常系: Published Group が見つからない場合、空の Resource が返されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromImageWithGroupNotFound(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $image = $this->createImage(ResourceType::GROUP, $groupId);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->mockAllRepositoriesExcept(['group']);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([], $resource->groupIds());
    }

    /**
     * 異常系: Published Talent が見つからない場合、空の Resource が返されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromImageWithTalentNotFound(): void
    {
        $talentId = StrTestHelper::generateUuid();
        $image = $this->createImage(ResourceType::TALENT, $talentId);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->mockAllRepositoriesExcept(['talent']);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([], $resource->groupIds());
        $this->assertSame([], $resource->talentIds());
    }

    /**
     * 異常系: Published Song が見つからない場合、空の Resource が返されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromImageWithSongNotFound(): void
    {
        $songId = StrTestHelper::generateUuid();
        $image = $this->createImage(ResourceType::SONG, $songId);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->mockAllRepositoriesExcept(['song']);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([], $resource->groupIds());
        $this->assertSame([], $resource->talentIds());
    }

    /**
     * 正常系: buildFromImage が ResourceType::IMAGE の場合、空の Resource を返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromImageWithImageType(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $image = $this->createImage(ResourceType::IMAGE, $imageId);

        $this->mockAllRepositories();

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([], $resource->groupIds());
        $this->assertSame([], $resource->talentIds());
    }

    private function mockAllRepositories(): void
    {
        $this->app->instance(DraftAgencyRepositoryInterface::class, Mockery::mock(DraftAgencyRepositoryInterface::class));
        $this->app->instance(DraftGroupRepositoryInterface::class, Mockery::mock(DraftGroupRepositoryInterface::class));
        $this->app->instance(DraftTalentRepositoryInterface::class, Mockery::mock(DraftTalentRepositoryInterface::class));
        $this->app->instance(DraftSongRepositoryInterface::class, Mockery::mock(DraftSongRepositoryInterface::class));
        $this->app->instance(AgencyRepositoryInterface::class, Mockery::mock(AgencyRepositoryInterface::class));
        $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));
        $this->app->instance(TalentRepositoryInterface::class, Mockery::mock(TalentRepositoryInterface::class));
        $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));
    }

    /**
     * @param array<string> $except
     */
    private function mockAllRepositoriesExcept(array $except): void
    {
        if (! in_array('draftAgency', $except, true)) {
            $this->app->instance(DraftAgencyRepositoryInterface::class, Mockery::mock(DraftAgencyRepositoryInterface::class));
        }
        if (! in_array('draftGroup', $except, true)) {
            $this->app->instance(DraftGroupRepositoryInterface::class, Mockery::mock(DraftGroupRepositoryInterface::class));
        }
        if (! in_array('draftTalent', $except, true)) {
            $this->app->instance(DraftTalentRepositoryInterface::class, Mockery::mock(DraftTalentRepositoryInterface::class));
        }
        if (! in_array('draftSong', $except, true)) {
            $this->app->instance(DraftSongRepositoryInterface::class, Mockery::mock(DraftSongRepositoryInterface::class));
        }
        if (! in_array('agency', $except, true)) {
            $this->app->instance(AgencyRepositoryInterface::class, Mockery::mock(AgencyRepositoryInterface::class));
        }
        if (! in_array('group', $except, true)) {
            $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));
        }
        if (! in_array('talent', $except, true)) {
            $this->app->instance(TalentRepositoryInterface::class, Mockery::mock(TalentRepositoryInterface::class));
        }
        if (! in_array('song', $except, true)) {
            $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));
        }
    }

    private function createDraftAgency(string $agencyId): DraftAgency
    {
        return new DraftAgency(
            new AgencyIdentifier($agencyId),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-agency'),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new Name('Test Agency'),
            'test agency',
            new CEO('Test CEO'),
            'test ceo',
            new FoundedIn(new DateTimeImmutable('2020-01-01')),
            new AgencyDescription('Test Description'),
            ApprovalStatus::Pending,
        );
    }

    private function createDraftGroup(string $groupId, ?string $agencyId): DraftGroup
    {
        return new DraftGroup(
            new GroupIdentifier($groupId),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-group'),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new GroupName('Test Group'),
            'test group',
            $agencyId !== null ? new GroupAgencyIdentifier($agencyId) : null,
            new GroupDescription('Test Description'),
            ApprovalStatus::Pending,
        );
    }

    /**
     * @param array<string> $groupIds
     */
    private function createDraftTalent(string $talentId, ?string $agencyId, array $groupIds): DraftTalent
    {
        return new DraftTalent(
            new TalentIdentifier($talentId),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-talent'),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new TalentName('Test Talent'),
            new RealName('Test Real Name'),
            $agencyId !== null ? new TalentAgencyIdentifier($agencyId) : null,
            array_map(static fn ($id) => new TalentGroupIdentifier($id), $groupIds),
            null,
            new Career('Test Career'),
            ApprovalStatus::Pending,
        );
    }

    private function createDraftSong(string $songId, ?string $agencyId, ?string $groupId, ?string $talentId): DraftSong
    {
        return new DraftSong(
            new SongIdentifier($songId),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-song'),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new SongName('Test Song'),
            $agencyId !== null ? new SongAgencyIdentifier($agencyId) : null,
            $groupId !== null ? new GroupIdentifier($groupId) : null,
            $talentId !== null ? new TalentIdentifier($talentId) : null,
            new Lyricist('Test Lyricist'),
            new Composer('Test Composer'),
            null,
            new Overview('Test Overview'),
            ApprovalStatus::Pending,
        );
    }

    private function createDraftImage(ResourceType $resourceType, string $resourceId): DraftImage
    {
        return new DraftImage(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            null,
            $resourceType,
            new ResourceIdentifier($resourceId),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.webp'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Test alt text',
            ApprovalStatus::UnderReview,
            new DateTimeImmutable(),
            new DateTimeImmutable(), // uploadedAt
        );
    }

    private function createImage(ResourceType $resourceType, string $resourceId): Image
    {
        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            $resourceType,
            new ResourceIdentifier($resourceId),
            new ImagePath('images/test.webp'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Test alt text',
            false,
            null,
            null,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            null,
            null,
        );
    }

    private function createAgency(string $agencyId): Agency
    {
        return new Agency(
            new AgencyIdentifier($agencyId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-agency'),
            Language::JAPANESE,
            new Name('Test Agency'),
            'test agency',
            new CEO('Test CEO'),
            'test ceo',
            new FoundedIn(new DateTimeImmutable('2020-01-01')),
            new AgencyDescription('Test Description'),
            new Version(1),
        );
    }

    private function createGroup(string $groupId, ?string $agencyId): Group
    {
        return new Group(
            new GroupIdentifier($groupId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-group'),
            Language::JAPANESE,
            new GroupName('Test Group'),
            'test group',
            $agencyId !== null ? new GroupAgencyIdentifier($agencyId) : null,
            new GroupDescription('Test Description'),
            new Version(1),
        );
    }

    /**
     * @param array<string> $groupIds
     */
    private function createTalent(string $talentId, ?string $agencyId, array $groupIds): Talent
    {
        return new Talent(
            new TalentIdentifier($talentId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-talent'),
            Language::JAPANESE,
            new TalentName('Test Talent'),
            new RealName('Test Real Name'),
            $agencyId !== null ? new TalentAgencyIdentifier($agencyId) : null,
            array_map(static fn ($id) => new \Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier($id), $groupIds),
            null,
            new Career('Test Career'),
            new Version(1),
        );
    }

    private function createSong(string $songId, ?string $agencyId, ?string $groupId, ?string $talentId): Song
    {
        return new Song(
            new SongIdentifier($songId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-song'),
            Language::JAPANESE,
            new SongName('Test Song'),
            $agencyId !== null ? new SongAgencyIdentifier($agencyId) : null,
            $groupId !== null ? new GroupIdentifier($groupId) : null,
            $talentId !== null ? new TalentIdentifier($talentId) : null,
            new Lyricist('Test Lyricist'),
            new Composer('Test Composer'),
            null,
            new Overview('Test Overview'),
            new Version(1),
        );
    }
}

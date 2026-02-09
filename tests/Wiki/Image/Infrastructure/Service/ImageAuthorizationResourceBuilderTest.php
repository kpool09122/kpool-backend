<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Image\Infrastructure\Service\ImageAuthorizationResourceBuilder;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
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
        $draftWiki = $this->createDraftWiki($agencyId, ResourceType::AGENCY, null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftWiki);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $basic = GroupBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => $agencyId,
        ]);

        $draftWiki = $this->createDraftWikiWithBasic($groupId, ResourceType::GROUP, $basic, null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftWiki);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $basic = GroupBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => null,
        ]);

        $draftWiki = $this->createDraftWikiWithBasic($groupId, ResourceType::GROUP, $basic, null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftWiki);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $basic = TalentBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => $agencyId,
            'group_identifiers' => [$groupId1, $groupId2],
        ]);

        $draftWiki = $this->createDraftWikiWithBasic($talentId, ResourceType::TALENT, $basic, null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftWiki);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $basic = TalentBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => null,
            'group_identifiers' => [],
        ]);

        $draftWiki = $this->createDraftWikiWithBasic($talentId, ResourceType::TALENT, $basic, null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftWiki);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $basic = SongBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => $agencyId,
            'group_identifiers' => [$groupId],
            'talent_identifiers' => [$talentId],
        ]);

        $draftWiki = $this->createDraftWikiWithBasic($songId, ResourceType::SONG, $basic, null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftWiki);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $basic = SongBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => null,
            'group_identifiers' => [],
            'talent_identifiers' => [],
        ]);

        $draftWiki = $this->createDraftWikiWithBasic($songId, ResourceType::SONG, $basic, null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftWiki);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::SONG, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
    }

    /**
     * 正常系: DraftAgency に publishedWikiIdentifier がある場合、publishedWikiIdentifier が selfIdentifier として使われること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildFromDraftResourceWithPublishedWikiIdentifier(): void
    {
        $draftWikiId = StrTestHelper::generateUuid();
        $publishedWikiId = StrTestHelper::generateUuid();
        $resourceIdentifier = new ResourceIdentifier($draftWikiId);
        $draftWiki = $this->createDraftWiki($draftWikiId, ResourceType::AGENCY, $publishedWikiId);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftWiki);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromDraftResource(ResourceType::AGENCY, $resourceIdentifier);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertSame($publishedWikiId, $resource->agencyId());
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

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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

        $basic = TalentBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => $agencyId,
            'group_identifiers' => [],
        ]);

        $draftWiki = $this->createDraftWikiWithBasic($talentId, ResourceType::TALENT, $basic, null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($draftWiki);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));

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
        $wiki = $this->createWiki($agencyId, ResourceType::AGENCY);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($wiki);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));

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

        $basic = GroupBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => $agencyId,
        ]);

        $wiki = $this->createWikiWithBasic($groupId, ResourceType::GROUP, $basic);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($wiki);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));

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

        $basic = TalentBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => $agencyId,
            'group_identifiers' => [$groupId],
        ]);

        $wiki = $this->createWikiWithBasic($talentId, ResourceType::TALENT, $basic);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($wiki);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));

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

        $basic = SongBasic::fromArray([
            'name' => 'test',
            'agency_identifier' => $agencyId,
            'group_identifiers' => [$groupId],
            'talent_identifiers' => [$talentId],
        ]);

        $wiki = $this->createWikiWithBasic($songId, ResourceType::SONG, $basic);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($wiki);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));

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

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));

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

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));

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

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));

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

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));

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

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));

        $builder = $this->app->make(ImageAuthorizationResourceBuilderInterface::class);
        $resource = $builder->buildFromImage($image);

        $this->assertSame(ResourceType::IMAGE, $resource->type());
        $this->assertNull($resource->agencyId());
        $this->assertSame([], $resource->groupIds());
        $this->assertSame([], $resource->talentIds());
    }

    private function mockAllRepositories(): void
    {
        $this->app->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));
    }

    private function createDraftWiki(string $id, ResourceType $resourceType, ?string $publishedWikiId): DraftWiki
    {
        $basic = AgencyBasic::fromArray(['name' => 'test']);

        return new DraftWiki(
            new DraftWikiIdentifier($id),
            $publishedWikiId !== null ? new WikiIdentifier($publishedWikiId) : null,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-slug'),
            Language::JAPANESE,
            $resourceType,
            $basic,
            new SectionContentCollection(),
            null,
            ApprovalStatus::Pending,
        );
    }

    private function createDraftWikiWithBasic(string $id, ResourceType $resourceType, BasicInterface $basic, ?string $publishedWikiId): DraftWiki
    {
        return new DraftWiki(
            new DraftWikiIdentifier($id),
            $publishedWikiId !== null ? new WikiIdentifier($publishedWikiId) : null,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-slug'),
            Language::JAPANESE,
            $resourceType,
            $basic,
            new SectionContentCollection(),
            null,
            ApprovalStatus::Pending,
        );
    }

    private function createWiki(string $id, ResourceType $resourceType): Wiki
    {
        $basic = AgencyBasic::fromArray(['name' => 'test']);

        return new Wiki(
            new WikiIdentifier($id),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-slug'),
            Language::JAPANESE,
            $resourceType,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );
    }

    private function createWikiWithBasic(string $id, ResourceType $resourceType, BasicInterface $basic): Wiki
    {
        return new Wiki(
            new WikiIdentifier($id),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('test-slug'),
            Language::JAPANESE,
            $resourceType,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
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
            new DateTimeImmutable(),
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
}

<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\CreateSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSong;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSongInput;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateSongTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        // TODO: 各実装クラス作ったら削除する
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $createSong = $this->app->make(CreateSongInterface::class);
        $this->assertInstanceOf(CreateSong::class, $createSong);
    }

    /**
     * 正常系：正しくSong Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $createDummyCreateSong = $this->createDummyCreateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new CreateSongInput(
            $createDummyCreateSong->publishedSongIdentifier,
            $createDummyCreateSong->language,
            $createDummyCreateSong->name,
            $createDummyCreateSong->agencyIdentifier,
            $createDummyCreateSong->groupIdentifier,
            $createDummyCreateSong->talentIdentifier,
            $createDummyCreateSong->lyricist,
            $createDummyCreateSong->composer,
            $createDummyCreateSong->releaseDate,
            $createDummyCreateSong->overView,
            $createDummyCreateSong->base64EncodedCoverImage,
            $createDummyCreateSong->musicVideoLink,
            $principalIdentifier
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($createDummyCreateSong->base64EncodedCoverImage)
            ->andReturn($createDummyCreateSong->coverImagePath);

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createDummyCreateSong->language, $createDummyCreateSong->name)
            ->andReturn($createDummyCreateSong->song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($createDummyCreateSong->publishedSongIdentifier)
            ->andReturn($createDummyCreateSong->publishedSong);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->once()
            ->with($createDummyCreateSong->song)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $createSong = $this->app->make(CreateSongInterface::class);
        $song = $createSong->process($input);
        $this->assertTrue(UuidValidator::isValid((string)$song->songIdentifier()));
        $this->assertSame((string)$createDummyCreateSong->publishedSongIdentifier, (string)$song->publishedSongIdentifier());
        $this->assertSame((string)$createDummyCreateSong->editorIdentifier, (string)$song->editorIdentifier());
        $this->assertSame($createDummyCreateSong->language->value, $song->language()->value);
        $this->assertSame((string)$createDummyCreateSong->name, (string)$song->name());
        $this->assertSame((string)$createDummyCreateSong->agencyIdentifier, (string)$song->agencyIdentifier());
        $this->assertSame((string)$createDummyCreateSong->groupIdentifier, (string)$song->groupIdentifier());
        $this->assertSame((string)$createDummyCreateSong->talentIdentifier, (string)$song->talentIdentifier());
        $this->assertSame((string)$createDummyCreateSong->lyricist, (string)$song->lyricist());
        $this->assertSame((string)$createDummyCreateSong->composer, (string)$song->composer());
        $this->assertSame($createDummyCreateSong->releaseDate->value(), $input->releaseDate()->value());
        $this->assertSame((string)$createDummyCreateSong->overView, (string)$song->overView());
        $this->assertSame((string)$createDummyCreateSong->coverImagePath, (string)$song->coverImagePath());
        $this->assertSame((string)$createDummyCreateSong->musicVideoLink, (string)$song->musicVideoLink());
        $this->assertSame($createDummyCreateSong->status, $song->status());
    }

    /**
     * 正常系：COLLABORATORがSongを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $createDummyCreateSong = $this->createDummyCreateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);

        $input = new CreateSongInput(
            $createDummyCreateSong->publishedSongIdentifier,
            $createDummyCreateSong->language,
            $createDummyCreateSong->name,
            $createDummyCreateSong->agencyIdentifier,
            $createDummyCreateSong->groupIdentifier,
            $createDummyCreateSong->talentIdentifier,
            $createDummyCreateSong->lyricist,
            $createDummyCreateSong->composer,
            $createDummyCreateSong->releaseDate,
            $createDummyCreateSong->overView,
            $createDummyCreateSong->base64EncodedCoverImage,
            $createDummyCreateSong->musicVideoLink,
            $principalIdentifier
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($createDummyCreateSong->base64EncodedCoverImage)
            ->andReturn($createDummyCreateSong->coverImagePath);

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createDummyCreateSong->language, $createDummyCreateSong->name)
            ->andReturn($createDummyCreateSong->song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($createDummyCreateSong->publishedSongIdentifier)
            ->andReturn($createDummyCreateSong->publishedSong);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->once()
            ->with($createDummyCreateSong->song)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $useCase = $this->app->make(CreateSongInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORがSongを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $createDummyCreateSong = $this->createDummyCreateSong();
        $agencyId = (string)$createDummyCreateSong->agencyIdentifier;

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new CreateSongInput(
            $createDummyCreateSong->publishedSongIdentifier,
            $createDummyCreateSong->language,
            $createDummyCreateSong->name,
            $createDummyCreateSong->agencyIdentifier,
            $createDummyCreateSong->groupIdentifier,
            $createDummyCreateSong->talentIdentifier,
            $createDummyCreateSong->lyricist,
            $createDummyCreateSong->composer,
            $createDummyCreateSong->releaseDate,
            $createDummyCreateSong->overView,
            $createDummyCreateSong->base64EncodedCoverImage,
            $createDummyCreateSong->musicVideoLink,
            $principalIdentifier
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($createDummyCreateSong->base64EncodedCoverImage)
            ->andReturn($createDummyCreateSong->coverImagePath);

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createDummyCreateSong->language, $createDummyCreateSong->name)
            ->andReturn($createDummyCreateSong->song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($createDummyCreateSong->publishedSongIdentifier)
            ->andReturn($createDummyCreateSong->publishedSong);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->once()
            ->with($createDummyCreateSong->song)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $useCase = $this->app->make(CreateSongInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：TALENT_ACTORがSongを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithTalentActor(): void
    {
        $createDummyCreateSong = $this->createDummyCreateSong();
        $agencyId = (string)$createDummyCreateSong->agencyIdentifier;
        $talentId = (string)$createDummyCreateSong->talentIdentifier;

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, [], [$talentId]);

        $input = new CreateSongInput(
            $createDummyCreateSong->publishedSongIdentifier,
            $createDummyCreateSong->language,
            $createDummyCreateSong->name,
            $createDummyCreateSong->agencyIdentifier,
            $createDummyCreateSong->groupIdentifier,
            $createDummyCreateSong->talentIdentifier,
            $createDummyCreateSong->lyricist,
            $createDummyCreateSong->composer,
            $createDummyCreateSong->releaseDate,
            $createDummyCreateSong->overView,
            $createDummyCreateSong->base64EncodedCoverImage,
            $createDummyCreateSong->musicVideoLink,
            $principalIdentifier
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($createDummyCreateSong->base64EncodedCoverImage)
            ->andReturn($createDummyCreateSong->coverImagePath);

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createDummyCreateSong->language, $createDummyCreateSong->name)
            ->andReturn($createDummyCreateSong->song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($createDummyCreateSong->publishedSongIdentifier)
            ->andReturn($createDummyCreateSong->publishedSong);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->once()
            ->with($createDummyCreateSong->song)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $useCase = $this->app->make(CreateSongInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORが曲を作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $createDummyCreateSong = $this->createDummyCreateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $input = new CreateSongInput(
            $createDummyCreateSong->publishedSongIdentifier,
            $createDummyCreateSong->language,
            $createDummyCreateSong->name,
            $createDummyCreateSong->agencyIdentifier,
            $createDummyCreateSong->groupIdentifier,
            $createDummyCreateSong->talentIdentifier,
            $createDummyCreateSong->lyricist,
            $createDummyCreateSong->composer,
            $createDummyCreateSong->releaseDate,
            $createDummyCreateSong->overView,
            $createDummyCreateSong->base64EncodedCoverImage,
            $createDummyCreateSong->musicVideoLink,
            $principalIdentifier
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($createDummyCreateSong->base64EncodedCoverImage)
            ->andReturn($createDummyCreateSong->coverImagePath);

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createDummyCreateSong->language, $createDummyCreateSong->name)
            ->andReturn($createDummyCreateSong->song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($createDummyCreateSong->publishedSongIdentifier)
            ->andReturn(null);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->once()
            ->with($createDummyCreateSong->song)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $useCase = $this->app->make(CreateSongInterface::class);
        $useCase->process($input);
    }

    /**
     * 異常系：NONEロールが曲を作成しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $createDummyCreateSong = $this->createDummyCreateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new CreateSongInput(
            $createDummyCreateSong->publishedSongIdentifier,
            $createDummyCreateSong->language,
            $createDummyCreateSong->name,
            $createDummyCreateSong->agencyIdentifier,
            $createDummyCreateSong->groupIdentifier,
            $createDummyCreateSong->talentIdentifier,
            $createDummyCreateSong->lyricist,
            $createDummyCreateSong->composer,
            $createDummyCreateSong->releaseDate,
            $createDummyCreateSong->overView,
            $createDummyCreateSong->base64EncodedCoverImage,
            $createDummyCreateSong->musicVideoLink,
            $principalIdentifier
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->expectException(UnauthorizedException::class);
        $useCase = $this->app->make(CreateSongInterface::class);
        $useCase->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $createDummyCreateSong = $this->createDummyCreateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new CreateSongInput(
            $createDummyCreateSong->publishedSongIdentifier,
            $createDummyCreateSong->language,
            $createDummyCreateSong->name,
            $createDummyCreateSong->agencyIdentifier,
            $createDummyCreateSong->groupIdentifier,
            $createDummyCreateSong->talentIdentifier,
            $createDummyCreateSong->lyricist,
            $createDummyCreateSong->composer,
            $createDummyCreateSong->releaseDate,
            $createDummyCreateSong->overView,
            $createDummyCreateSong->base64EncodedCoverImage,
            $createDummyCreateSong->musicVideoLink,
            $principalIdentifier
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase = $this->app->make(CreateSongInterface::class);
        $useCase->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return CreateSongTestData
     */
    private function createDummyCreateSong(): CreateSongTestData
    {
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $base64EncodedCoverImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $coverImagePath = new ImagePath('/resources/public/images/before.webp');

        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $status = ApprovalStatus::Pending;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $version = new Version(1);
        $publishedSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $version,
        );

        return new CreateSongTestData(
            $publishedSongIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $coverImagePath,
            $songIdentifier,
            $status,
            $translationSetIdentifier,
            $song,
            $publishedSong,
            $version,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class CreateSongTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public SongIdentifier       $publishedSongIdentifier,
        public PrincipalIdentifier  $editorIdentifier,
        public Language             $language,
        public SongName            $name,
        public AgencyIdentifier    $agencyIdentifier,
        public GroupIdentifier     $groupIdentifier,
        public TalentIdentifier    $talentIdentifier,
        public Lyricist            $lyricist,
        public Composer            $composer,
        public ReleaseDate         $releaseDate,
        public Overview            $overView,
        public string              $base64EncodedCoverImage,
        public ExternalContentLink $musicVideoLink,
        public ImagePath           $coverImagePath,
        public SongIdentifier $songIdentifier,
        public ApprovalStatus $status,
        public TranslationSetIdentifier $translationSetIdentifier,
        public DraftSong $song,
        public Song $publishedSong,
        public Version $version,
    ) {
    }
}

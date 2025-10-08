<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\CreateSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSong;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSongInput;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
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
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $createSong = $this->app->make(CreateSongInterface::class);
        $this->assertInstanceOf(CreateSong::class, $createSong);
    }

    /**
     * 正常系：正しくSong Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $base64EncodedCoverImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new CreateSongInput(
            $publishedSongIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $principal
        );

        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedCoverImage)
            ->andReturn($coverImagePath);

        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $publishedSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($editorIdentifier, $translation, $name)
            ->andReturn($song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($song)
            ->andReturn(null);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($publishedSongIdentifier)
            ->andReturn($publishedSong);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $createSong = $this->app->make(CreateSongInterface::class);
        $song = $createSong->process($input);
        $this->assertTrue(UlidValidator::isValid((string)$song->songIdentifier()));
        $this->assertSame((string)$publishedSongIdentifier, (string)$song->publishedSongIdentifier());
        $this->assertSame((string)$editorIdentifier, (string)$song->editorIdentifier());
        $this->assertSame($translation->value, $song->translation()->value);
        $this->assertSame((string)$name, (string)$song->name());
        $this->assertSame($belongIdentifiers, $song->belongIdentifiers());
        $this->assertSame((string)$lyricist, (string)$song->lyricist());
        $this->assertSame((string)$composer, (string)$song->composer());
        $this->assertSame($releaseDate->value(), $input->releaseDate()->value());
        $this->assertSame((string)$overView, (string)$song->overView());
        $this->assertSame((string)$coverImagePath, (string)$song->coverImagePath());
        $this->assertSame((string)$musicVideoLink, (string)$song->musicVideoLink());
        $this->assertSame($status, $song->status());
    }

    /**
     * 正常系：COLLABORATORがSongを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testProcessWithCollaborator(): void
    {
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다.');
        $base64EncodedCoverImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);

        $input = new CreateSongInput(
            $publishedSongIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $principal
        );

        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedCoverImage)
            ->andReturn($coverImagePath);

        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $publishedSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($editorIdentifier, $translation, $name)
            ->andReturn($song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($song)
            ->andReturn(null);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($publishedSongIdentifier)
            ->andReturn($publishedSong);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $useCase = $this->app->make(CreateSongInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(DraftSong::class, $result);
    }

    /**
     * 正常系：AGENCY_ACTORがSongを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testProcessWithAgencyActor(): void
    {
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $agencyId = StrTestHelper::generateUlid();
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다.');
        $base64EncodedCoverImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], null);

        $input = new CreateSongInput(
            $publishedSongIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $principal
        );

        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedCoverImage)
            ->andReturn($coverImagePath);

        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $publishedSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($editorIdentifier, $translation, $name)
            ->andReturn($song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($song)
            ->andReturn(null);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($publishedSongIdentifier)
            ->andReturn($publishedSong);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $useCase = $this->app->make(CreateSongInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(DraftSong::class, $result);
    }

    /**
     * 正常系：GROUP_ACTORがSongを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testProcessWithGroupActor(): void
    {
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $groupId = StrTestHelper::generateUlid();
        $belongIdentifiers = [
            new BelongIdentifier($groupId),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다.');
        $base64EncodedCoverImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$groupId], null);

        $input = new CreateSongInput(
            $publishedSongIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $principal
        );

        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedCoverImage)
            ->andReturn($coverImagePath);

        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $publishedSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($editorIdentifier, $translation, $name)
            ->andReturn($song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($song)
            ->andReturn(null);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($publishedSongIdentifier)
            ->andReturn($publishedSong);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $useCase = $this->app->make(CreateSongInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(DraftSong::class, $result);
    }

    /**
     * 正常系：TALENT_ACTORがSongを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testProcessWithTalentActor(): void
    {
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $groupId = StrTestHelper::generateUlid();
        $belongIdentifiers = [
            new BelongIdentifier($groupId),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다.');
        $base64EncodedCoverImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$groupId], $talentId);

        $input = new CreateSongInput(
            $publishedSongIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $principal
        );

        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedCoverImage)
            ->andReturn($coverImagePath);

        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $publishedSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($editorIdentifier, $translation, $name)
            ->andReturn($song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($song)
            ->andReturn(null);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($publishedSongIdentifier)
            ->andReturn($publishedSong);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $useCase = $this->app->make(CreateSongInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(DraftSong::class, $result);
    }

    /**
     * 正常系：SENIOR_COLLABORATORが曲を作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('Test overview');
        $base64EncodedCoverImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA';
        $musicVideoLink = new ExternalContentLink('https://www.youtube.com/watch?v=ePpPVE-GGJw');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], null);

        $input = new CreateSongInput(
            $publishedSongIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $principal
        );

        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedCoverImage)
            ->andReturn($coverImagePath);

        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $songFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($editorIdentifier, $translation, $name)
            ->andReturn($song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($song)
            ->andReturn(null);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($publishedSongIdentifier)
            ->andReturn(null);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftSongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $useCase = $this->app->make(CreateSongInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(DraftSong::class, $result);
    }

    /**
     * 異常系：NONEロールが曲を作成しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWithNoneRole(): void
    {
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('Test overview');
        $base64EncodedCoverImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA';
        $musicVideoLink = new ExternalContentLink('https://www.youtube.com/watch?v=ePpPVE-GGJw');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], null);

        $input = new CreateSongInput(
            $publishedSongIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $principal
        );

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $useCase = $this->app->make(CreateSongInterface::class);
        $useCase->process($input);
    }
}

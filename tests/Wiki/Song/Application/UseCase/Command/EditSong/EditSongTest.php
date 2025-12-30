<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\EditSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\ImageServiceInterface;
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
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\UseCase\Command\EditSong\EditSong;
use Source\Wiki\Song\Application\UseCase\Command\EditSong\EditSongInput;
use Source\Wiki\Song\Application\UseCase\Command\EditSong\EditSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class EditSongTest extends TestCase
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
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $editSong = $this->app->make(EditSongInterface::class);
        $this->assertInstanceOf(EditSong::class, $editSong);
    }

    /**
     * 正常系：正しくSong Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $dummyEditSong = $this->createDummyEditSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new EditSongInput(
            $dummyEditSong->songIdentifier,
            $dummyEditSong->name,
            $dummyEditSong->agencyIdentifier,
            $dummyEditSong->belongIdentifiers,
            $dummyEditSong->lyricist,
            $dummyEditSong->composer,
            $dummyEditSong->releaseDate,
            $dummyEditSong->overView,
            $dummyEditSong->base64EncodedCoverImage,
            $dummyEditSong->musicVideoLink,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($dummyEditSong->base64EncodedCoverImage)
            ->andReturn($dummyEditSong->coverImagePath);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyEditSong->song)
            ->andReturn(null);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyEditSong->songIdentifier)
            ->andReturn($dummyEditSong->song);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $editSong = $this->app->make(EditSongInterface::class);
        $song = $editSong->process($input);
        $this->assertSame((string)$dummyEditSong->songIdentifier, (string)$song->songIdentifier());
        $this->assertSame((string)$dummyEditSong->publishedSongIdentifier, (string)$song->publishedSongIdentifier());
        $this->assertSame((string)$dummyEditSong->editorIdentifier, (string)$song->editorIdentifier());
        $this->assertSame($dummyEditSong->language->value, $song->language()->value);
        $this->assertSame((string)$dummyEditSong->name, (string)$song->name());
        $this->assertSame((string)$dummyEditSong->agencyIdentifier, (string)$song->agencyIdentifier());
        $this->assertSame($dummyEditSong->belongIdentifiers, $song->belongIdentifiers());
        $this->assertSame((string)$dummyEditSong->lyricist, (string)$song->lyricist());
        $this->assertSame((string)$dummyEditSong->composer, (string)$song->composer());
        $this->assertSame($dummyEditSong->releaseDate->value(), $song->releaseDate()->value());
        $this->assertSame((string)$dummyEditSong->overView, (string)$song->overView());
        $this->assertSame((string)$dummyEditSong->coverImagePath, (string)$song->coverImagePath());
        $this->assertSame((string)$dummyEditSong->musicVideoLink, (string)$song->musicVideoLink());
        $this->assertSame($dummyEditSong->status, $song->status());
    }

    /**
     * 異常系：指定したIDに紐づくSongがない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundSong(): void
    {
        $dummyEditSong = $this->createDummyEditSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        $input = new EditSongInput(
            $dummyEditSong->songIdentifier,
            $dummyEditSong->name,
            $dummyEditSong->agencyIdentifier,
            $dummyEditSong->belongIdentifiers,
            $dummyEditSong->lyricist,
            $dummyEditSong->composer,
            $dummyEditSong->releaseDate,
            $dummyEditSong->overView,
            $dummyEditSong->base64EncodedCoverImage,
            $dummyEditSong->musicVideoLink,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyEditSong->songIdentifier)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->expectException(SongNotFoundException::class);
        $editSong = $this->app->make(EditSongInterface::class);
        $editSong->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws SongNotFoundException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyEditSong = $this->createDummyEditSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        $input = new EditSongInput(
            $dummyEditSong->songIdentifier,
            $dummyEditSong->name,
            $dummyEditSong->agencyIdentifier,
            $dummyEditSong->belongIdentifiers,
            $dummyEditSong->lyricist,
            $dummyEditSong->composer,
            $dummyEditSong->releaseDate,
            $dummyEditSong->overView,
            $dummyEditSong->base64EncodedCoverImage,
            $dummyEditSong->musicVideoLink,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyEditSong->songIdentifier)
            ->andReturn($dummyEditSong->song);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $editSong = $this->app->make(EditSongInterface::class);
        $editSong->process($input);
    }

    /**
     * 正常系：COLLABORATORが曲を編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $dummyEditSong = $this->createDummyEditSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::COLLABORATOR, null, [], []);

        $input = new EditSongInput(
            $dummyEditSong->songIdentifier,
            $dummyEditSong->name,
            $dummyEditSong->agencyIdentifier,
            $dummyEditSong->belongIdentifiers,
            $dummyEditSong->lyricist,
            $dummyEditSong->composer,
            $dummyEditSong->releaseDate,
            $dummyEditSong->overView,
            null,
            $dummyEditSong->musicVideoLink,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyEditSong->songIdentifier)
            ->andReturn($dummyEditSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyEditSong->song)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $editSong = $this->app->make(EditSongInterface::class);
        $editSong->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが曲を編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $dummyEditSong = $this->createDummyEditSong();
        $agencyId = (string)$dummyEditSong->agencyIdentifier;

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new EditSongInput(
            $dummyEditSong->songIdentifier,
            $dummyEditSong->name,
            $dummyEditSong->agencyIdentifier,
            $dummyEditSong->belongIdentifiers,
            $dummyEditSong->lyricist,
            $dummyEditSong->composer,
            $dummyEditSong->releaseDate,
            $dummyEditSong->overView,
            null,
            $dummyEditSong->musicVideoLink,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyEditSong->songIdentifier)
            ->andReturn($dummyEditSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyEditSong->song)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $editSong = $this->app->make(EditSongInterface::class);
        $editSong->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが曲を編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithGroupActor(): void
    {
        $dummyEditSong = $this->createDummyEditSong();
        $agencyId = (string)$dummyEditSong->agencyIdentifier;
        $belongIds = array_map(static fn ($belongId) => (string)$belongId, $dummyEditSong->belongIdentifiers);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::GROUP_ACTOR, $agencyId, $belongIds, []);

        $input = new EditSongInput(
            $dummyEditSong->songIdentifier,
            $dummyEditSong->name,
            $dummyEditSong->agencyIdentifier,
            $dummyEditSong->belongIdentifiers,
            $dummyEditSong->lyricist,
            $dummyEditSong->composer,
            $dummyEditSong->releaseDate,
            $dummyEditSong->overView,
            null,
            $dummyEditSong->musicVideoLink,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyEditSong->songIdentifier)
            ->andReturn($dummyEditSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyEditSong->song)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $editSong = $this->app->make(EditSongInterface::class);
        $editSong->process($input);
    }

    /**
     * 正常系：TALENT_ACTORが曲を編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithTalentActor(): void
    {
        $dummyEditSong = $this->createDummyEditSong();
        $agencyId = (string)$dummyEditSong->agencyIdentifier;
        $belongIds = array_map(static fn ($belongId) => (string)$belongId, $dummyEditSong->belongIdentifiers);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::TALENT_ACTOR, $agencyId, $belongIds, []);

        $input = new EditSongInput(
            $dummyEditSong->songIdentifier,
            $dummyEditSong->name,
            $dummyEditSong->agencyIdentifier,
            $dummyEditSong->belongIdentifiers,
            $dummyEditSong->lyricist,
            $dummyEditSong->composer,
            $dummyEditSong->releaseDate,
            $dummyEditSong->overView,
            null,
            $dummyEditSong->musicVideoLink,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyEditSong->songIdentifier)
            ->andReturn($dummyEditSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyEditSong->song)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $editSong = $this->app->make(EditSongInterface::class);
        $editSong->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORが曲を編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyEditSong = $this->createDummyEditSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $input = new EditSongInput(
            $dummyEditSong->songIdentifier,
            $dummyEditSong->name,
            $dummyEditSong->agencyIdentifier,
            $dummyEditSong->belongIdentifiers,
            $dummyEditSong->lyricist,
            $dummyEditSong->composer,
            $dummyEditSong->releaseDate,
            $dummyEditSong->overView,
            null,
            $dummyEditSong->musicVideoLink,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyEditSong->songIdentifier)
            ->andReturn($dummyEditSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyEditSong->song)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $editSong = $this->app->make(EditSongInterface::class);
        $editSong->process($input);
    }

    /**
     * 異常系：NONEロールが曲を編集しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummyEditSong = $this->createDummyEditSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::NONE, null, [], []);

        $input = new EditSongInput(
            $dummyEditSong->songIdentifier,
            $dummyEditSong->name,
            $dummyEditSong->agencyIdentifier,
            $dummyEditSong->belongIdentifiers,
            $dummyEditSong->lyricist,
            $dummyEditSong->composer,
            $dummyEditSong->releaseDate,
            $dummyEditSong->overView,
            null,
            $dummyEditSong->musicVideoLink,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyEditSong->songIdentifier)
            ->andReturn($dummyEditSong->song);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $editSong = $this->app->make(EditSongInterface::class);
        $editSong->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return EditSongTestData
     */
    private function createDummyEditSong(): EditSongTestData
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
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
        $coverImagePath = new ImagePath('/resources/public/images/before.webp');

        $status = ApprovalStatus::Pending;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        return new EditSongTestData(
            $songIdentifier,
            $publishedSongIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $coverImagePath,
            $status,
            $translationSetIdentifier,
            $song,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class EditSongTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param BelongIdentifier[] $belongIdentifiers
     */
    public function __construct(
        public SongIdentifier      $songIdentifier,
        public SongIdentifier      $publishedSongIdentifier,
        public EditorIdentifier    $editorIdentifier,
        public Language            $language,
        public SongName            $name,
        public AgencyIdentifier    $agencyIdentifier,
        public array               $belongIdentifiers,
        public Lyricist            $lyricist,
        public Composer            $composer,
        public ReleaseDate         $releaseDate,
        public Overview            $overView,
        public string              $base64EncodedCoverImage,
        public ExternalContentLink $musicVideoLink,
        public ImagePath           $coverImagePath,
        public ApprovalStatus $status,
        public TranslationSetIdentifier $translationSetIdentifier,
        public DraftSong $song,
    ) {
    }
}

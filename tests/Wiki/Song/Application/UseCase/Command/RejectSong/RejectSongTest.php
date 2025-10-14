<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\RejectSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\UseCase\Command\RejectSong\RejectSong;
use Source\Wiki\Song\Application\UseCase\Command\RejectSong\RejectSongInput;
use Source\Wiki\Song\Application\UseCase\Command\RejectSong\RejectSongInterface;
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

class RejectSongTest extends TestCase
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
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $rejectSong = $this->app->make(RejectSongInterface::class);
        $this->assertInstanceOf(RejectSong::class, $rejectSong);
    }

    /**
     * 正常系：正しく下書きが拒否されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectSong->song)
            ->andReturn(null);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $rejectSong = $this->app->make(RejectSongInterface::class);
        $song = $rejectSong->process($input);
        $this->assertNotSame($dummyRejectSong->status, $song->status());
        $this->assertSame(ApprovalStatus::Rejected, $song->status());
    }

    /**
     * 異常系：指定したIDに紐づくSongが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundMember(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new RejectSongInput(
            $songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($songIdentifier)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(SongNotFoundException::class);
        $rejectSong = $this->app->make(RejectSongInterface::class);
        $rejectSong->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     */
    public function testInvalidStatus(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        // ステータスがApprovedの場合は例外が発生する
        $status = ApprovalStatus::Approved;
        $song = new DraftSong(
            $dummyRejectSong->songIdentifier,
            $dummyRejectSong->publishedSongIdentifier,
            $dummyRejectSong->translationSetIdentifier,
            $dummyRejectSong->editorIdentifier,
            $dummyRejectSong->translation,
            $dummyRejectSong->name,
            $dummyRejectSong->agencyIdentifier,
            $dummyRejectSong->belongIdentifiers,
            $dummyRejectSong->lyricist,
            $dummyRejectSong->composer,
            $dummyRejectSong->releaseDate,
            $dummyRejectSong->overView,
            $dummyRejectSong->coverImagePath,
            $dummyRejectSong->musicVideoLink,
            $status,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($song);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(InvalidStatusException::class);
        $rejectSong = $this->app->make(RejectSongInterface::class);
        $rejectSong->process($input);
    }

    /**
     * 異常系：承認権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedRole(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectSong = $this->app->make(RejectSongInterface::class);
        $rejectSong->process($input);
    }

    /**
     * 正常系：ADMINISTRATORが歌を却下できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithAdministrator(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectSong->song)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $rejectSong = $this->app->make(RejectSongInterface::class);
        $result = $rejectSong->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：AGENCY_ACTORが自分の所属していないグループの歌を却下しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, $anotherAgencyId, [], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectSong = $this->app->make(RejectSongInterface::class);
        $rejectSong->process($input);
    }

    /**
     * 正常系： AGENCY_ACTORが自分の所属するグループの楽曲を却下できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();
        $agencyId = (string) $dummyRejectSong->agencyIdentifier;

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectSong->song)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $rejectSong = $this->app->make(RejectSongInterface::class);
        $result = $rejectSong->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループの歌を却下しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $dummyRejectSong->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, $agencyId, [$anotherGroupId], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectSong = $this->app->make(RejectSongInterface::class);
        $rejectSong->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループの歌を却下できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedGroupActor(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();
        $agencyId = (string) $dummyRejectSong->agencyIdentifier;
        $belongIds = array_map(static fn ($belongId) => (string)$belongId, $dummyRejectSong->belongIdentifiers);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, $agencyId, $belongIds, null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectSong->song)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $rejectSong = $this->app->make(RejectSongInterface::class);
        $result = $rejectSong->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：TALENT_ACTORが自分の所属していないグループの歌を却下しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $dummyRejectSong->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, $agencyId, [$anotherGroupId], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectSong = $this->app->make(RejectSongInterface::class);
        $rejectSong->process($input);
    }

    /**
     * 正常系：TALENT_ACTORが自分の所属するグループの歌を却下できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedTalentActor(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();
        $agencyId = (string) $dummyRejectSong->agencyIdentifier;
        $belongIds = array_map(static fn ($belongId) => (string)$belongId, $dummyRejectSong->belongIdentifiers);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, $agencyId, $belongIds, null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectSong->song)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $rejectSong = $this->app->make(RejectSongInterface::class);
        $result = $rejectSong->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 正常系：SENIOR_COLLABORATORが歌を却下できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyRejectSong->song)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $rejectSong = $this->app->make(RejectSongInterface::class);
        $result = $rejectSong->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：NONEロールが歌を却下しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyRejectSong = $this->createDummyRejectSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], null);

        $input = new RejectSongInput(
            $dummyRejectSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyRejectSong->songIdentifier)
            ->andReturn($dummyRejectSong->song);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectSong = $this->app->make(RejectSongInterface::class);
        $rejectSong->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return RejectSongTestData
     */
    private function createDummyRejectSong(): RejectSongTestData
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
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
        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $status = ApprovalStatus::UnderReview;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
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

        return new RejectSongTestData(
            $songIdentifier,
            $publishedSongIdentifier,
            $editorIdentifier,
            $translation,
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
            $translationSetIdentifier,
            $song,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class RejectSongTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param BelongIdentifier[] $belongIdentifiers
     */
    public function __construct(
        public SongIdentifier $songIdentifier,
        public SongIdentifier $publishedSongIdentifier,
        public EditorIdentifier $editorIdentifier,
        public Translation $translation,
        public SongName $name,
        public AgencyIdentifier $agencyIdentifier,
        public array $belongIdentifiers,
        public Lyricist $lyricist,
        public Composer $composer,
        public ReleaseDate $releaseDate,
        public Overview $overView,
        public ImagePath $coverImagePath,
        public ExternalContentLink $musicVideoLink,
        public ApprovalStatus $status,
        public TranslationSetIdentifier $translationSetIdentifier,
        public DraftSong $song,
    ) {
    }
}

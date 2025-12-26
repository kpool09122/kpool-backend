<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\SubmitSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\UseCase\Command\SubmitSong\SubmitSong;
use Source\Wiki\Song\Application\UseCase\Command\SubmitSong\SubmitSongInput;
use Source\Wiki\Song\Application\UseCase\Command\SubmitSong\SubmitSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongHistoryIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitSongTest extends TestCase
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
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $submitSong = $this->app->make(SubmitSongInterface::class);
        $this->assertInstanceOf(SubmitSong::class, $submitSong);
    }

    /**
     * 正常系：正しく下書きステータスが変更されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $dummySubmitSong = $this->createDummySubmitSong(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitSong->song)
            ->andReturn(null);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitSong->history);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitSong->history)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $submitSong = $this->app->make(SubmitSongInterface::class);
        $song = $submitSong->process($input);
        $this->assertNotSame($dummySubmitSong->status, $song->status());
        $this->assertSame(ApprovalStatus::UnderReview, $song->status());
    }

    /**
     * 異常系：指定したIDに紐づくSongが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundAgency(): void
    {
        $dummySubmitSong = $this->createDummySubmitSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn(null);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(SongNotFoundException::class);
        $submitSong = $this->app->make(SubmitSongInterface::class);
        $submitSong->process($input);
    }

    /**
     * 異常系：承認ステータスがPendingかRejected以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     */
    public function testInvalidStatus(): void
    {
        $dummySubmitSong = $this->createDummySubmitSong(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $submitSong = $this->app->make(SubmitSongInterface::class);
        $submitSong->process($input);
    }

    /**
     * 正常系：COLLABORATORが楽曲を申請できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::COLLABORATOR, null, [], []);

        $dummySubmitSong = $this->createDummySubmitSong(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitSong->song)
            ->andReturn(null);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitSong->history);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitSong->history)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $useCase = $this->app->make(SubmitSongInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：AGENCY_ACTORが楽曲を申請できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $dummySubmitSong = $this->createDummySubmitSong(
            agencyId: $agencyId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitSong->song)
            ->andReturn(null);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitSong->history);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitSong->history)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $useCase = $this->app->make(SubmitSongInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：GROUP_ACTORが楽曲を申請できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithGroupActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $belongIds = [StrTestHelper::generateUlid(), StrTestHelper::generateUlid()];
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::GROUP_ACTOR, $agencyId, $belongIds, []);

        $dummySubmitSong = $this->createDummySubmitSong(
            agencyId: $agencyId,
            belongIds: $belongIds,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitSong->song)
            ->andReturn(null);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitSong->history);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitSong->history)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $useCase = $this->app->make(SubmitSongInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：TALENT_ACTORが楽曲を申請できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithTalentActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $belongIds = [StrTestHelper::generateUlid(), StrTestHelper::generateUlid()];
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::TALENT_ACTOR, $agencyId, $belongIds, []);

        $dummySubmitSong = $this->createDummySubmitSong(
            agencyId: $agencyId,
            belongIds: $belongIds,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitSong->song)
            ->andReturn(null);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitSong->history);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitSong->history)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $useCase = $this->app->make(SubmitSongInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：SENIOR_COLLABORATORが曲を提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $dummySubmitSong = $this->createDummySubmitSong(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);
        $songRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummySubmitSong->song)
            ->andReturn(null);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummySubmitSong->history);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitSong->history)
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $useCase = $this->app->make(SubmitSongInterface::class);
        $result = $useCase->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 異常系：NONEロールが曲を提出しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummySubmitSong = $this->createDummySubmitSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::NONE, null, [], []);

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $useCase = $this->app->make(SubmitSongInterface::class);
        $useCase->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $agencyId
     * @param array<string>|null $belongIds
     * @param ApprovalStatus $status
     * @param EditorIdentifier|null $operatorIdentifier
     * @return SubmitSongTestData
     */
    private function createDummySubmitSong(
        ?string $agencyId = null,
        ?array $belongIds = null,
        ApprovalStatus $status = ApprovalStatus::Pending,
        ?EditorIdentifier $operatorIdentifier = null,
    ): SubmitSongTestData {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUlid());
        $belongIdentifiers = $belongIds !== null
            ? array_map(static fn ($id) => new BelongIdentifier($id), $belongIds)
            : [
                new BelongIdentifier(StrTestHelper::generateUlid()),
                new BelongIdentifier(StrTestHelper::generateUlid()),
            ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

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

        $historyIdentifier = new SongHistoryIdentifier(StrTestHelper::generateUlid());
        $history = new SongHistory(
            $historyIdentifier,
            $operatorIdentifier ?? new EditorIdentifier(StrTestHelper::generateUlid()),
            $song->editorIdentifier(),
            $song->publishedSongIdentifier(),
            $song->songIdentifier(),
            $status,
            ApprovalStatus::UnderReview,
            $song->name(),
            new DateTimeImmutable('now'),
        );

        return new SubmitSongTestData(
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
            $coverImagePath,
            $musicVideoLink,
            $status,
            $translationSetIdentifier,
            $song,
            $historyIdentifier,
            $history,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class SubmitSongTestData
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
        public ImagePath           $coverImagePath,
        public ExternalContentLink $musicVideoLink,
        public ApprovalStatus      $status,
        public TranslationSetIdentifier $translationSetIdentifier,
        public DraftSong $song,
        public SongHistoryIdentifier $historyIdentifier,
        public SongHistory $history,
    ) {
    }
}

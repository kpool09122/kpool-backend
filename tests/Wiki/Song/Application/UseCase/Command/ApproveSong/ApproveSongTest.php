<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\ApproveSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\UseCase\Command\ApproveSong\ApproveSong;
use Source\Wiki\Song\Application\UseCase\Command\ApproveSong\ApproveSongInput;
use Source\Wiki\Song\Application\UseCase\Command\ApproveSong\ApproveSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongHistoryIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveSongTest extends TestCase
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
        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $songService = Mockery::mock(SongServiceInterface::class);
        $this->app->instance(SongServiceInterface::class, $songService);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $approveSong = $this->app->make(ApproveSongInterface::class);
        $this->assertInstanceOf(ApproveSong::class, $approveSong);
    }

    /**
     * 正常系：正しく下書きが承認されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $dummyApproveSong = $this->createDummyApproveSong(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new ApproveSongInput(
            $dummyApproveSong->songIdentifier,
            $dummyApproveSong->publishedSongIdentifier,
            $principalIdentifier,
        );

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->once()
            ->with($dummyApproveSong->song)
            ->andReturn(null);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveSong->songIdentifier)
            ->andReturn($dummyApproveSong->song);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyApproveSong->translationSetIdentifier, $dummyApproveSong->songIdentifier)
            ->andReturn(false);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyApproveSong->history);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyApproveSong->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $approveSong = $this->app->make(ApproveSongInterface::class);
        $song = $approveSong->process($input);
        $this->assertNotSame($dummyApproveSong->status, $song->status());
        $this->assertSame(ApprovalStatus::Approved, $song->status());
    }

    /**
     * 異常系：指定したIDに紐づくSongが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundMember(): void
    {
        $dummyApproveSong = $this->createDummyApproveSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveSongInput(
            $dummyApproveSong->songIdentifier,
            $dummyApproveSong->publishedSongIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveSong->songIdentifier)
            ->andReturn(null);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(SongNotFoundException::class);
        $approveSong = $this->app->make(ApproveSongInterface::class);
        $approveSong->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws SongNotFoundException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyApproveSong = $this->createDummyApproveSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveSongInput(
            $dummyApproveSong->songIdentifier,
            $dummyApproveSong->publishedSongIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveSong->songIdentifier)
            ->andReturn($dummyApproveSong->song);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(PrincipalNotFoundException::class);
        $approveSong = $this->app->make(ApproveSongInterface::class);
        $approveSong->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummyApproveSong = $this->createDummyApproveSong(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveSongInput(
            $dummyApproveSong->songIdentifier,
            $dummyApproveSong->publishedSongIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveSong->songIdentifier)
            ->andReturn($dummyApproveSong->song);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $approveSong = $this->app->make(ApproveSongInterface::class);
        $approveSong->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みの事務所がある場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testHasApprovedButNotTranslatedAgency(): void
    {
        $dummyApproveSong = $this->createDummyApproveSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new ApproveSongInput(
            $dummyApproveSong->songIdentifier,
            $dummyApproveSong->publishedSongIdentifier,
            $principalIdentifier,
        );

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveSong->songIdentifier)
            ->andReturn($dummyApproveSong->song);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyApproveSong->translationSetIdentifier, $dummyApproveSong->songIdentifier)
            ->andReturn(true);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(ExistsApprovedButNotTranslatedSongException::class);
        $approveSong = $this->app->make(ApproveSongInterface::class);
        $approveSong->process($input);
    }

    /**
     * 異常系：権限がない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorized(): void
    {
        $dummyApproveSong = $this->createDummyApproveSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new ApproveSongInput(
            $dummyApproveSong->songIdentifier,
            $dummyApproveSong->publishedSongIdentifier,
            $principalIdentifier,
        );

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveSong->songIdentifier)
            ->andReturn($dummyApproveSong->song);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveSong = $this->app->make(ApproveSongInterface::class);
        $approveSong->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $agencyId
     * @param string|null $groupId
     * @param string|null $talentId
     * @param ApprovalStatus $status
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @return ApproveSongTestData
     */
    private function createDummyApproveSong(
        ?string $agencyId = null,
        ?string $groupId = null,
        ?string $talentId = null,
        ApprovalStatus $status = ApprovalStatus::UnderReview,
        ?PrincipalIdentifier $operatorIdentifier = null,
    ): ApproveSongTestData {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier($groupId ?? StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier($talentId ?? StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

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

        $historyIdentifier = new SongHistoryIdentifier(StrTestHelper::generateUuid());
        $history = new SongHistory(
            $historyIdentifier,
            HistoryActionType::DraftStatusChange,
            $operatorIdentifier ?? new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $song->editorIdentifier(),
            $song->publishedSongIdentifier(),
            $song->songIdentifier(),
            ApprovalStatus::UnderReview,
            ApprovalStatus::Approved,
            null,
            null,
            $song->name(),
            new DateTimeImmutable('now'),
        );

        return new ApproveSongTestData(
            $songIdentifier,
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
readonly class ApproveSongTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public SongIdentifier           $songIdentifier,
        public SongIdentifier           $publishedSongIdentifier,
        public PrincipalIdentifier       $editorIdentifier,
        public Language                 $language,
        public SongName                 $name,
        public AgencyIdentifier         $agencyIdentifier,
        public GroupIdentifier          $groupIdentifier,
        public TalentIdentifier         $talentIdentifier,
        public Lyricist                 $lyricist,
        public Composer                 $composer,
        public ReleaseDate              $releaseDate,
        public Overview                 $overView,
        public ImagePath                $coverImagePath,
        public ExternalContentLink      $musicVideoLink,
        public ApprovalStatus           $status,
        public TranslationSetIdentifier $translationSetIdentifier,
        public DraftSong                $song,
        public SongHistoryIdentifier    $historyIdentifier,
        public SongHistory              $history,
    ) {
    }
}

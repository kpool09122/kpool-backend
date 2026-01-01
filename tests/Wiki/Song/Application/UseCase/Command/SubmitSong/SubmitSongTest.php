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
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
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
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
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
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $dummySubmitSong = $this->createDummySubmitSong(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

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

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
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
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundAgency(): void
    {
        $dummySubmitSong = $this->createDummySubmitSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn(null);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(SongNotFoundException::class);
        $submitSong = $this->app->make(SubmitSongInterface::class);
        $submitSong->process($input);
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
        $dummySubmitSong = $this->createDummySubmitSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
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
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(PrincipalNotFoundException::class);
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
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummySubmitSong = $this->createDummySubmitSong(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
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
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
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
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);

        $dummySubmitSong = $this->createDummySubmitSong(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
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

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
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
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $dummySubmitSong = $this->createDummySubmitSong(
            agencyId: $agencyId,
            operatorIdentifier: $principalIdentifier,
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
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

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
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
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithGroupActor(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, $agencyId, [$groupId], []);

        $dummySubmitSong = $this->createDummySubmitSong(
            agencyId: $agencyId,
            groupId: $groupId,
            operatorIdentifier: $principalIdentifier,
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
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

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
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
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithTalentActor(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, [], [$talentId]);

        $dummySubmitSong = $this->createDummySubmitSong(
            agencyId: $agencyId,
            talentId: $talentId,
            operatorIdentifier: $principalIdentifier,
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
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

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
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
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $dummySubmitSong = $this->createDummySubmitSong(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
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

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
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
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummySubmitSong = $this->createDummySubmitSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new SubmitSongInput(
            $dummySubmitSong->songIdentifier,
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
            ->with($dummySubmitSong->songIdentifier)
            ->andReturn($dummySubmitSong->song);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
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
     * @param string|null $groupId
     * @param string|null $talentId
     * @param ApprovalStatus $status
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @return SubmitSongTestData
     */
    private function createDummySubmitSong(
        ?string $agencyId = null,
        ?string $groupId = null,
        ?string $talentId = null,
        ApprovalStatus $status = ApprovalStatus::Pending,
        ?PrincipalIdentifier $operatorIdentifier = null,
    ): SubmitSongTestData {
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
            $operatorIdentifier ?? new PrincipalIdentifier(StrTestHelper::generateUuid()),
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
readonly class SubmitSongTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public SongIdentifier      $songIdentifier,
        public SongIdentifier      $publishedSongIdentifier,
        public PrincipalIdentifier       $editorIdentifier,
        public Language            $language,
        public SongName            $name,
        public AgencyIdentifier    $agencyIdentifier,
        public ?GroupIdentifier    $groupIdentifier,
        public ?TalentIdentifier   $talentIdentifier,
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

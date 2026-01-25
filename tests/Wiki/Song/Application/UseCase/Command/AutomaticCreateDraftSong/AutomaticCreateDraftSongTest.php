<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong\AutomaticCreateDraftSongInput;
use Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong\AutomaticCreateDraftSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\AutomaticDraftSongCreationServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongSource;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutomaticCreateDraftSongTest extends TestCase
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
        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $repository = Mockery::mock(DraftSongRepositoryInterface::class);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);
        $this->assertInstanceOf(AutomaticCreateDraftSongInterface::class, $useCase);
    }

    /**
     * 正常系: ActorがAdministratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $payload = $this->makePayload();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = $this->makePrincipal($principalIdentifier);
        $draftSong = $this->makeDraftSong();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftSong);

        $repository = Mockery::mock(DraftSongRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->with($draftSong)
            ->andReturnNull();

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($draftSong, $result);
    }

    /**
     * 正常系: ActorがSenior Collaboratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $payload = $this->makePayload();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = $this->makePrincipal($principalIdentifier);
        $draftSong = $this->makeDraftSong();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftSong);

        $repository = Mockery::mock(DraftSongRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->with($draftSong)
            ->andReturnNull();

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($draftSong, $result);
    }

    /**
     * 異常系: ActorがAdministratorかSenior Collaboratorでない場合は、例外がスローされること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithUnauthorizedRole(): void
    {
        $payload = $this->makePayload();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = $this->makePrincipal($principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $repository = Mockery::mock(DraftSongRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $this->setPolicyEvaluatorResult(false);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $this->expectException(UnauthorizedException::class);
        $useCase->process($input);
    }

    /**
     * 異常系: 指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $payload = $this->makePayload();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $repository = Mockery::mock(DraftSongRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase->process($input);
    }

    private function makePayload(): AutomaticDraftSongCreationPayload
    {
        return new AutomaticDraftSongCreationPayload(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('Auto Song'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new Lyricist('Auto Lyricist'),
            new Composer('Auto Composer'),
            new ReleaseDate(new DateTimeImmutable('2024-02-10')),
            new Overview('Auto generated song overview.'),
            new AutomaticDraftSongSource('news::auto-songs'),
        );
    }

    private function makePrincipal(PrincipalIdentifier $principalIdentifier): Principal
    {
        return new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
    }

    private function makeDraftSong(): DraftSong
    {
        return new DraftSong(
            new SongIdentifier(StrTestHelper::generateUuid()),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('ttt'),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('TT'),
            null,
            null,
            null,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            null,
            new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.'),
            ApprovalStatus::Pending,
        );
    }
}

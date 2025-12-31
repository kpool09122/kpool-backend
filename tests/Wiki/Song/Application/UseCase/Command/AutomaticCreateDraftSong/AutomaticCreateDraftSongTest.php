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
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong\AutomaticCreateDraftSongInput;
use Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong\AutomaticCreateDraftSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\AutomaticDraftSongCreationServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongSource;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
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
        $repository = Mockery::mock(SongRepositoryInterface::class);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SongRepositoryInterface::class, $repository);
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
        $principal = $this->makePrincipal(Role::ADMINISTRATOR, $principalIdentifier);
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

        $repository = Mockery::mock(SongRepositoryInterface::class);
        $repository->shouldReceive('saveDraft')
            ->once()
            ->with($draftSong)
            ->andReturnNull();

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SongRepositoryInterface::class, $repository);

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
        $principal = $this->makePrincipal(Role::SENIOR_COLLABORATOR, $principalIdentifier);
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

        $repository = Mockery::mock(SongRepositoryInterface::class);
        $repository->shouldReceive('saveDraft')
            ->once()
            ->with($draftSong)
            ->andReturnNull();

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SongRepositoryInterface::class, $repository);

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
        $principal = $this->makePrincipal(Role::AGENCY_ACTOR, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $repository = Mockery::mock(SongRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
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
        $repository = Mockery::mock(SongRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase->process($input);
    }

    private function makePayload(): AutomaticDraftSongCreationPayload
    {
        return new AutomaticDraftSongCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('Auto Song'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new BelongIdentifier(StrTestHelper::generateUuid()),
                new BelongIdentifier(StrTestHelper::generateUuid()),
            ],
            new Lyricist('Auto Lyricist'),
            new Composer('Auto Composer'),
            new ReleaseDate(new DateTimeImmutable('2024-02-10')),
            new Overview('Auto generated song overview.'),
            new AutomaticDraftSongSource('news::auto-songs'),
        );
    }

    private function makePrincipal(Role $role, PrincipalIdentifier $principalIdentifier): Principal
    {
        return new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $role, null, [], []);
    }

    private function makeDraftSong(): DraftSong
    {
        return new DraftSong(
            new SongIdentifier(StrTestHelper::generateUuid()),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new EditorIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('Auto Song'),
            null,
            [],
            new Lyricist('Draft Lyricist'),
            new Composer('Draft Composer'),
            null,
            new Overview('Auto generated overview.'),
            null,
            null,
            ApprovalStatus::Pending,
        );
    }
}

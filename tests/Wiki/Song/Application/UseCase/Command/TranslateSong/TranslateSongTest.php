<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\TranslateSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Mockery\MockInterface;
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
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\Service\TranslatedSongData;
use Source\Wiki\Song\Application\Service\TranslationServiceInterface;
use Source\Wiki\Song\Application\UseCase\Command\TranslateSong\TranslateSong;
use Source\Wiki\Song\Application\UseCase\Command\TranslateSong\TranslateSongInput;
use Source\Wiki\Song\Application\UseCase\Command\TranslateSong\TranslateSongInterface;
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

class TranslateSongTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $songService = Mockery::mock(TranslationServiceInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $songService);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $this->assertInstanceOf(TranslateSong::class, $translateSong);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $dummyTranslateSong = $this->createDummyTranslateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($dummyTranslateSong->songIdentifier)
            ->once()
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateSong->jaTranslatedData);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateSong->enTranslatedData);

        $draftSongFactory = $this->createDraftSongFactoryMock($dummyTranslateSong);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(DraftSongFactoryInterface::class, $draftSongFactory);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $songs = $translateSong->process($input);
        $this->assertCount(2, $songs);
        $this->assertInstanceOf(DraftSong::class, $songs[0]);
        $this->assertInstanceOf(DraftSong::class, $songs[1]);
    }

    /**
     * 異常系： 指定したIDの歌情報が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenSongNotFound(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateSongInput(
            $songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($songIdentifier)
            ->once()
            ->andReturn(null);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->expectException(SongNotFoundException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
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
        $dummyTranslateSong = $this->createDummyTranslateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTranslateSong->songIdentifier)
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
    }

    /**
     * 異常系：翻訳権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $dummyTranslateSong = $this->createDummyTranslateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTranslateSong->songIdentifier)
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
    }

    /**
     * 正常系：ADMINISTRATORが楽曲を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $dummyTranslateSong = $this->createDummyTranslateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($dummyTranslateSong->songIdentifier)
            ->once()
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateSong->jaTranslatedData);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateSong->enTranslatedData);

        $draftSongFactory = $this->createDraftSongFactoryMock($dummyTranslateSong);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(DraftSongFactoryInterface::class, $draftSongFactory);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $songs = $translateSong->process($input);
        $this->assertCount(2, $songs);
    }

    /**
     * 異常系：AGENCY_ACTORが自分の所属していないグループの楽曲を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyTranslateSong = $this->createDummyTranslateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherAgencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $anotherAgencyId, [], []);

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTranslateSong->songIdentifier)
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の所属するグループの楽曲を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $dummyTranslateSong = $this->createDummyTranslateSong();
        $agencyId = (string) $dummyTranslateSong->agencyIdentifier;

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [], []);

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($dummyTranslateSong->songIdentifier)
            ->once()
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateSong->jaTranslatedData);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateSong->enTranslatedData);

        $draftSongFactory = $this->createDraftSongFactoryMock($dummyTranslateSong);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(DraftSongFactoryInterface::class, $draftSongFactory);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $songs = $translateSong->process($input);
        $this->assertCount(2, $songs);
    }

    /**
     * 異常系：TALENT_ACTORが自分の所属していないグループの楽曲を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $dummyTranslateSong = $this->createDummyTranslateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $dummyTranslateSong->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [$anotherGroupId], []);

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTranslateSong->songIdentifier)
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
    }

    /**
     * 正常系：TALENT_ACTORが自分の所属するグループの楽曲を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedTalentActor(): void
    {
        $dummyTranslateSong = $this->createDummyTranslateSong();
        $agencyId = (string) $dummyTranslateSong->agencyIdentifier;
        $talentId = (string) $dummyTranslateSong->talentIdentifier;

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [], [$talentId]);

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($dummyTranslateSong->songIdentifier)
            ->once()
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateSong->jaTranslatedData);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateSong->enTranslatedData);

        $draftSongFactory = $this->createDraftSongFactoryMock($dummyTranslateSong);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(DraftSongFactoryInterface::class, $draftSongFactory);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $songs = $translateSong->process($input);
        $this->assertCount(2, $songs);
    }

    /**
     * 正常系：SENIOR_COLLABORATORが曲を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyTranslateSong = $this->createDummyTranslateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($dummyTranslateSong->songIdentifier)
            ->once()
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateSong->jaTranslatedData);
        $translationService->shouldReceive('translateSong')
            ->with($dummyTranslateSong->song, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateSong->enTranslatedData);

        $draftSongFactory = $this->createDraftSongFactoryMock($dummyTranslateSong);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->app->instance(DraftSongFactoryInterface::class, $draftSongFactory);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $songs = $translateSong->process($input);
        $this->assertCount(2, $songs);
    }

    /**
     * 異常系：NONEロールが曲を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyTranslateSong = $this->createDummyTranslateSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateSongInput(
            $dummyTranslateSong->songIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($dummyTranslateSong->songIdentifier)
            ->once()
            ->andReturn($dummyTranslateSong->song);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
    }

    /**
     * DraftSongFactoryのモックを作成するヘルパーメソッド
     *
     * @param TranslateSongTestData $dummyTranslateSong
     * @return MockInterface&DraftSongFactoryInterface
     */
    private function createDraftSongFactoryMock(TranslateSongTestData $dummyTranslateSong): MockInterface
    {
        /** @var MockInterface&DraftSongFactoryInterface $draftSongFactory */
        $draftSongFactory = Mockery::mock(DraftSongFactoryInterface::class);
        $draftSongFactory->shouldReceive('create')
            ->with(
                null,
                $dummyTranslateSong->song->slug(),
                Language::JAPANESE,
                Mockery::type(SongName::class),
                $dummyTranslateSong->song->translationSetIdentifier(),
            )
            ->once()
            ->andReturn($dummyTranslateSong->jaSong);
        $draftSongFactory->shouldReceive('create')
            ->with(
                null,
                $dummyTranslateSong->song->slug(),
                Language::ENGLISH,
                Mockery::type(SongName::class),
                $dummyTranslateSong->song->translationSetIdentifier(),
            )
            ->once()
            ->andReturn($dummyTranslateSong->enSong);

        return $draftSongFactory;
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return TranslateSongTestData
     */
    private function createDummyTranslateSong(): TranslateSongTestData
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('ttt');
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다.');
        $version = new Version(1);

        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $version,
            editorIdentifier: $editorIdentifier,
        );

        // 日本語版 DraftSong
        $jaSong = new DraftSong(
            new SongIdentifier(StrTestHelper::generateUuid()),
            null,
            $translationSetIdentifier,
            $slug,
            null,
            Language::JAPANESE,
            new SongName('TT'),
            null,
            null,
            null,
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            ApprovalStatus::Pending,
        );

        // 英語版 DraftSong
        $enSong = new DraftSong(
            new SongIdentifier(StrTestHelper::generateUuid()),
            null,
            $translationSetIdentifier,
            $slug,
            null,
            Language::ENGLISH,
            new SongName('TT'),
            null,
            null,
            null,
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            ApprovalStatus::Pending,
        );

        $jaTranslatedData = new TranslatedSongData(
            translatedName: 'TT',
            translatedLyricist: 'Black Eyed Pilseung',
            translatedComposer: 'Sam Lewis',
            translatedOverview: '「TT」は初めて恋に落ちた少女の仕方がない心を歌った曲です。',
        );

        $enTranslatedData = new TranslatedSongData(
            translatedName: 'TT',
            translatedLyricist: 'Black Eyed Pilseung',
            translatedComposer: 'Sam Lewis',
            translatedOverview: '"TT" is a song about the helpless feelings of a girl who\'s fallen in love for the first time.',
        );

        return new TranslateSongTestData(
            $songIdentifier,
            $editorIdentifier,
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
            $song,
            $jaSong,
            $enSong,
            $jaTranslatedData,
            $enTranslatedData,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class TranslateSongTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public SongIdentifier           $songIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Language                 $language,
        public SongName                 $name,
        public AgencyIdentifier         $agencyIdentifier,
        public ?GroupIdentifier         $groupIdentifier,
        public ?TalentIdentifier        $talentIdentifier,
        public Lyricist                 $lyricist,
        public Composer                 $composer,
        public ReleaseDate              $releaseDate,
        public Overview                 $overView,
        public Song                     $song,
        public DraftSong                $jaSong,
        public DraftSong                $enSong,
        public TranslatedSongData       $jaTranslatedData,
        public TranslatedSongData       $enTranslatedData,
    ) {
    }
}

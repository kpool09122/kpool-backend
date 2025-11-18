<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\PublishSong;

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
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSong;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSongInput;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;
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

class PublishSongTest extends TestCase
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
        $songService = Mockery::mock(SongServiceInterface::class);
        $this->app->instance(SongServiceInterface::class, $songService);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $this->assertInstanceOf(PublishSong::class, $publishSong);
    }

    /**
     * 正常系：正しく変更されたSongが公開されること（すでに一度公開されたことがある場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWhenAlreadyPublished(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishSong->publishedSongIdentifier)
            ->andReturn($dummyPublishSong->publishedSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->publishedSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishSong->draftSong)
            ->andReturn(null);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishedSong = $publishSong->process($input);
        $this->assertSame((string)$dummyPublishSong->publishedSongIdentifier, (string)$publishedSong->songIdentifier());
        $this->assertSame($dummyPublishSong->translation->value, $publishedSong->translation()->value);
        $this->assertSame((string)$dummyPublishSong->name, (string)$publishedSong->name());
        $this->assertSame((string)$dummyPublishSong->agencyIdentifier, (string)$publishedSong->agencyIdentifier());
        $this->assertSame($dummyPublishSong->belongIdentifiers, $publishedSong->belongIdentifiers());
        $this->assertSame((string)$dummyPublishSong->lyricist, (string)$publishedSong->lyricist());
        $this->assertSame((string)$dummyPublishSong->composer, (string)$publishedSong->composer());
        $this->assertSame($dummyPublishSong->releaseDate->value(), $publishedSong->releaseDate()->value());
        $this->assertSame((string)$dummyPublishSong->overView, (string)$publishedSong->overView());
        $this->assertSame((string)$dummyPublishSong->coverImagePath, (string)$publishedSong->coverImagePath());
        $this->assertSame((string)$dummyPublishSong->musicVideoLink, (string)$publishedSong->musicVideoLink());
        $this->assertSame($dummyPublishSong->publishedVersion->value() + 1, $publishedSong->version()->value());
    }

    /**
     * 正常系：正しく変更されたSongが公開されること（初めて公開する場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessForTheFirstTime(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        // 初回公開なのでpublishedSongIdentifierをnullにする
        $draftSong = new DraftSong(
            $dummyPublishSong->songIdentifier,
            null,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->editorIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            $dummyPublishSong->belongIdentifiers,
            $dummyPublishSong->lyricist,
            $dummyPublishSong->composer,
            $dummyPublishSong->releaseDate,
            $dummyPublishSong->overView,
            $dummyPublishSong->coverImagePath,
            $dummyPublishSong->musicVideoLink,
            $dummyPublishSong->status,
        );

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        $version = new Version(1);
        $createdSong = new Song(
            $dummyPublishSong->publishedSongIdentifier,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            [],
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
            $version,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->translation, $dummyPublishSong->name)
            ->andReturn($createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishedSong = $publishSong->process($input);
        $this->assertSame((string)$dummyPublishSong->publishedSongIdentifier, (string)$publishedSong->songIdentifier());
        $this->assertSame($dummyPublishSong->translation->value, $publishedSong->translation()->value);
        $this->assertSame((string)$dummyPublishSong->name, (string)$publishedSong->name());
        $this->assertSame((string)$dummyPublishSong->agencyIdentifier, (string)$publishedSong->agencyIdentifier());
        $this->assertSame($dummyPublishSong->belongIdentifiers, $publishedSong->belongIdentifiers());
        $this->assertSame((string)$dummyPublishSong->lyricist, (string)$publishedSong->lyricist());
        $this->assertSame((string)$dummyPublishSong->composer, (string)$publishedSong->composer());
        $this->assertSame($dummyPublishSong->releaseDate->value(), $publishedSong->releaseDate()->value());
        $this->assertSame((string)$dummyPublishSong->overView, (string)$publishedSong->overView());
        $this->assertSame((string)$dummyPublishSong->coverImagePath, (string)$publishedSong->coverImagePath());
        $this->assertSame((string)$dummyPublishSong->musicVideoLink, (string)$publishedSong->musicVideoLink());
        $this->assertSame($version->value(), $publishedSong->version()->value());
    }

    /**
     * 異常系：指定したIDに紐づくSongが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     */
    public function testWhenNotFoundSong(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn(null);

        $songService = Mockery::mock(SongServiceInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(SongNotFoundException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
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
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        // ステータスがApprovedの場合は例外が発生する
        $song = new DraftSong(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->editorIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            $dummyPublishSong->belongIdentifiers,
            $dummyPublishSong->lyricist,
            $dummyPublishSong->composer,
            $dummyPublishSong->releaseDate,
            $dummyPublishSong->overView,
            $dummyPublishSong->coverImagePath,
            $dummyPublishSong->musicVideoLink,
            ApprovalStatus::Approved,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($song);

        $songService = Mockery::mock(SongServiceInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(InvalidStatusException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みの歌がある場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testHasApprovedButNotTranslatedSong(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(true);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(ExistsApprovedButNotTranslatedSongException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 異常系：公開されているメンバー情報が取得できない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPublishedSong(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishSong->publishedSongIdentifier)
            ->andReturn(null);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(SongNotFoundException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
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
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(UnauthorizedException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 異常系：Agency_ACTORが自分の所属していないグループの楽曲を公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(UnauthorizedException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の所属するグループの歌を公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        // 初回公開なのでpublishedSongIdentifierをnullにする
        $draftSong = new DraftSong(
            $dummyPublishSong->songIdentifier,
            null,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->editorIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            $dummyPublishSong->belongIdentifiers,
            $dummyPublishSong->lyricist,
            $dummyPublishSong->composer,
            $dummyPublishSong->releaseDate,
            $dummyPublishSong->overView,
            $dummyPublishSong->coverImagePath,
            $dummyPublishSong->musicVideoLink,
            $dummyPublishSong->status,
        );

        $version = new Version(1);
        $createdSong = new Song(
            $dummyPublishSong->publishedSongIdentifier,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            [],
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
            $version,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->translation, $dummyPublishSong->name)
            ->andReturn($createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongServiceInterface::class, $songService);

        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループの楽曲を公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, $agencyId, [$anotherGroupId], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(UnauthorizedException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループの楽曲を公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedGroupActor(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;
        $groupId = (string)$dummyPublishSong->belongIdentifiers[0];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, $agencyId, [$groupId], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        // 初回公開なのでpublishedSongIdentifierをnullにする
        $draftSong = new DraftSong(
            $dummyPublishSong->songIdentifier,
            null,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->editorIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            $dummyPublishSong->belongIdentifiers,
            $dummyPublishSong->lyricist,
            $dummyPublishSong->composer,
            $dummyPublishSong->releaseDate,
            $dummyPublishSong->overView,
            $dummyPublishSong->coverImagePath,
            $dummyPublishSong->musicVideoLink,
            $dummyPublishSong->status,
        );

        $version = new Version(1);
        $createdSong = new Song(
            $dummyPublishSong->publishedSongIdentifier,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            [],
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
            $version,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->translation, $dummyPublishSong->name)
            ->andReturn($createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongServiceInterface::class, $songService);

        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 異常系：TALENT_ACTORが自分の所属していないグループの楽曲を公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, $agencyId, [$anotherGroupId], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(UnauthorizedException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 正常系：TALENT_ACTORが自分の所属するグループの楽曲を公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testAuthorizedTalentActor(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;
        $groupId = (string)$dummyPublishSong->belongIdentifiers[0];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, $agencyId, [$groupId], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        // 初回公開なのでpublishedSongIdentifierをnullにする
        $draftSong = new DraftSong(
            $dummyPublishSong->songIdentifier,
            null,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->editorIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            $dummyPublishSong->belongIdentifiers,
            $dummyPublishSong->lyricist,
            $dummyPublishSong->composer,
            $dummyPublishSong->releaseDate,
            $dummyPublishSong->overView,
            $dummyPublishSong->coverImagePath,
            $dummyPublishSong->musicVideoLink,
            $dummyPublishSong->status,
        );

        $version = new Version(1);
        $createdSong = new Song(
            $dummyPublishSong->publishedSongIdentifier,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            [],
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
            $version,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->translation, $dummyPublishSong->name)
            ->andReturn($createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongServiceInterface::class, $songService);

        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORが曲を公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        // 初回公開なのでpublishedSongIdentifierをnullにする
        $draftSong = new DraftSong(
            $dummyPublishSong->songIdentifier,
            null,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->editorIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            $dummyPublishSong->belongIdentifiers,
            $dummyPublishSong->lyricist,
            $dummyPublishSong->composer,
            $dummyPublishSong->releaseDate,
            $dummyPublishSong->overView,
            $dummyPublishSong->coverImagePath,
            $dummyPublishSong->musicVideoLink,
            $dummyPublishSong->status,
        );

        $version = new Version(1);
        $createdSong = new Song(
            $dummyPublishSong->publishedSongIdentifier,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            [],
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
            $version,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->translation, $dummyPublishSong->name)
            ->andReturn($createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongServiceInterface::class, $songService);

        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishedSong = $publishSong->process($input);

        $this->assertInstanceOf(Song::class, $publishedSong);
    }

    /**
     * 異常系：NONEロールが曲を公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principal,
        );

        // 初回公開なのでpublishedSongIdentifierをnullにする
        $draftSong = new DraftSong(
            $dummyPublishSong->songIdentifier,
            null,
            $dummyPublishSong->translationSetIdentifier,
            $dummyPublishSong->editorIdentifier,
            $dummyPublishSong->translation,
            $dummyPublishSong->name,
            $dummyPublishSong->agencyIdentifier,
            $dummyPublishSong->belongIdentifiers,
            $dummyPublishSong->lyricist,
            $dummyPublishSong->composer,
            $dummyPublishSong->releaseDate,
            $dummyPublishSong->overView,
            $dummyPublishSong->coverImagePath,
            $dummyPublishSong->musicVideoLink,
            $dummyPublishSong->status,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(UnauthorizedException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return PublishSongTestData
     */
    private function createDummyPublishSong(): PublishSongTestData
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
        $draftSong = new DraftSong(
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

        // 公開済みのSongエンティティ（既存データを想定）
        $publishedName = new SongName('I CAN\'T STOP ME');
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedBelongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $publishedLyricist = new Lyricist('J.Y. Park');
        $publishedComposer = new Composer('Melanie Joy Fontana');
        $publishedReleaseDate = new ReleaseDate(new DateTimeImmutable('2020-10-26'));
        $publishedOverView = new Overview('\'I CAN\'T STOP ME\'는 80년대 신시사이저 사운드가 특징인 업템포의 레트로풍 댄스곡입니다. 가사는 선과 악의 갈림길에서 자기 자신을 제어하기 힘들어지는 갈등과, 멈출 수 없는 위험한 감정에 이끌리는 마음을 표현하고 있습니다. 파워풀한 퍼포먼스와 함께 트와이스의 새로운 매력을 보여준 곡으로 높은 평가를 받고 있습니다.');
        $publishedCoverImagePath = new ImagePath('/resources/public/images/after.webp');
        $publishedMusicVideoLink = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $publishedVersion = new Version(1);

        $publishedSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $translation,
            $publishedName,
            $publishedAgencyIdentifier,
            $publishedBelongIdentifiers,
            $publishedLyricist,
            $publishedComposer,
            $publishedReleaseDate,
            $publishedOverView,
            $publishedCoverImagePath,
            $publishedMusicVideoLink,
            $publishedVersion,
        );

        return new PublishSongTestData(
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
            $draftSong,
            $publishedSong,
            $publishedVersion,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class PublishSongTestData
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
        public DraftSong $draftSong,
        public Song $publishedSong,
        public Version $publishedVersion,
    ) {
    }
}

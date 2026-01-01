<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\PublishSong;

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
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSong;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSongInput;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\Entity\SongSnapshot;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongSnapshotFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongSnapshotRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongHistoryIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Domain\ValueObject\SongSnapshotIdentifier;
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
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $songService = Mockery::mock(SongServiceInterface::class);
        $this->app->instance(SongServiceInterface::class, $songService);
        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $this->assertInstanceOf(PublishSong::class, $publishSong);
    }

    /**
     * 正常系：正しく変更されたSongが公開されること（すでに一度公開されたことがある場合）.
     * スナップショットが保存されることも確認.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWhenAlreadyPublished(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $dummyPublishSong = $this->createDummyPublishSong(
            hasPublishedSong: true,
            operatorIdentifier: $principalIdentifier,
        );

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishSong->history);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->history)
            ->andReturn(null);

        // スナップショットのモック
        $snapshot = $dummyPublishSong->snapshot;
        $songSnapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);
        $songSnapshotFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->publishedSong)
            ->andReturn($snapshot);

        $songSnapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $songSnapshotRepository->shouldReceive('save')
            ->once()
            ->with($snapshot)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongSnapshotFactoryInterface::class, $songSnapshotFactory);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $songSnapshotRepository);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishedSong = $publishSong->process($input);
        $this->assertSame((string)$dummyPublishSong->publishedSongIdentifier, (string)$publishedSong->songIdentifier());
        $this->assertSame($dummyPublishSong->language->value, $publishedSong->language()->value);
        $this->assertSame((string)$dummyPublishSong->name, (string)$publishedSong->name());
        $this->assertSame((string)$dummyPublishSong->agencyIdentifier, (string)$publishedSong->agencyIdentifier());
        $this->assertSame((string)$dummyPublishSong->groupIdentifier, (string)$publishedSong->groupIdentifier());
        $this->assertSame((string)$dummyPublishSong->talentIdentifier, (string)$publishedSong->talentIdentifier());
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
     * @throws PrincipalNotFoundException
     */
    public function testProcessForTheFirstTime(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $dummyPublishSong = $this->createDummyPublishSong(
            hasPublishedSong: false,
            operatorIdentifier: $principalIdentifier,
        );

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishSong->draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->language, $dummyPublishSong->name)
            ->andReturn($dummyPublishSong->createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishSong->history);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishedSong = $publishSong->process($input);
        $this->assertSame((string)$dummyPublishSong->publishedSongIdentifier, (string)$publishedSong->songIdentifier());
        $this->assertSame($dummyPublishSong->language->value, $publishedSong->language()->value);
        $this->assertSame((string)$dummyPublishSong->translationSetIdentifier, (string)$publishedSong->translationSetIdentifier());
        $this->assertSame((string)$dummyPublishSong->agencyIdentifier, (string)$publishedSong->agencyIdentifier());
        $this->assertSame((string)$dummyPublishSong->agencyIdentifier, (string)$publishedSong->agencyIdentifier());
        $this->assertSame((string)$dummyPublishSong->groupIdentifier, (string)$publishedSong->groupIdentifier());
        $this->assertSame((string)$dummyPublishSong->talentIdentifier, (string)$publishedSong->talentIdentifier());
        $this->assertSame((string)$dummyPublishSong->lyricist, (string)$publishedSong->lyricist());
        $this->assertSame((string)$dummyPublishSong->composer, (string)$publishedSong->composer());
        $this->assertSame($dummyPublishSong->releaseDate->value(), $publishedSong->releaseDate()->value());
        $this->assertSame((string)$dummyPublishSong->overView, (string)$publishedSong->overView());
        $this->assertSame((string)$dummyPublishSong->coverImagePath, (string)$publishedSong->coverImagePath());
        $this->assertSame((string)$dummyPublishSong->musicVideoLink, (string)$publishedSong->musicVideoLink());
        $this->assertSame($dummyPublishSong->version->value(), $publishedSong->version()->value());
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
    public function testWhenNotFoundSong(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn(null);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(SongNotFoundException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
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
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(PrincipalNotFoundException::class);
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
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testHasApprovedButNotTranslatedSong(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(true);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundPublishedSong(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong(hasPublishedSong: true);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherAgencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $dummyPublishSong = $this->createDummyPublishSong(
            hasPublishedSong: false,
            operatorIdentifier: $principalIdentifier,
        );
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;

        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishSong->draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->language, $dummyPublishSong->name)
            ->andReturn($dummyPublishSong->createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishSong->history);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, $agencyId, [$anotherGroupId], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedGroupActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $dummyPublishSong = $this->createDummyPublishSong(
            hasPublishedSong: false,
            operatorIdentifier: $principalIdentifier,
        );
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;
        $groupId = (string)$dummyPublishSong->groupIdentifier;

        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, $agencyId, [$groupId], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishSong->draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->language, $dummyPublishSong->name)
            ->andReturn($dummyPublishSong->createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishSong->history);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, [$anotherGroupId], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedTalentActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $dummyPublishSong = $this->createDummyPublishSong(
            hasPublishedSong: false,
            operatorIdentifier: $principalIdentifier,
        );
        $agencyId = (string) $dummyPublishSong->agencyIdentifier;
        $talentId = (string)$dummyPublishSong->talentIdentifier;

        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, [], [$talentId]);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishSong->draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->language, $dummyPublishSong->name)
            ->andReturn($dummyPublishSong->createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishSong->history);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $dummyPublishSong = $this->createDummyPublishSong(
            hasPublishedSong: false,
            operatorIdentifier: $principalIdentifier,
        );

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishSong->draftSong)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->language, $dummyPublishSong->name)
            ->andReturn($dummyPublishSong->createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($dummyPublishSong->translationSetIdentifier, $dummyPublishSong->songIdentifier)
            ->andReturn(false);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishSong->history);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishSong->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyPublishSong = $this->createDummyPublishSong();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new PublishSongInput(
            $dummyPublishSong->songIdentifier,
            $dummyPublishSong->publishedSongIdentifier,
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
            ->with($dummyPublishSong->songIdentifier)
            ->andReturn($dummyPublishSong->draftSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param ApprovalStatus $status
     * @param bool $hasPublishedSong
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @return PublishSongTestData
     */
    private function createDummyPublishSong(
        ApprovalStatus $status = ApprovalStatus::UnderReview,
        bool $hasPublishedSong = false,
        ?PrincipalIdentifier $operatorIdentifier = null,
    ): PublishSongTestData {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $draftSong = new DraftSong(
            $songIdentifier,
            $hasPublishedSong ? $publishedSongIdentifier : null,
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

        // 公開済みのSongエンティティ（既存データを想定）
        $publishedName = new SongName('I CAN\'T STOP ME');
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
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
            $language,
            $publishedName,
            $publishedAgencyIdentifier,
            $publishedGroupIdentifier,
            $publishedTalentIdentifier,
            $publishedLyricist,
            $publishedComposer,
            $publishedReleaseDate,
            $publishedOverView,
            $publishedCoverImagePath,
            $publishedMusicVideoLink,
            $publishedVersion,
        );

        // 新規作成用のSong
        $version = new Version(1);
        $createdSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            null,
            null,
            null,
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
            $version,
        );

        $historyIdentifier = new SongHistoryIdentifier(StrTestHelper::generateUuid());
        $history = new SongHistory(
            $historyIdentifier,
            $operatorIdentifier ?? new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $draftSong->editorIdentifier(),
            $hasPublishedSong ? $publishedSongIdentifier : null,
            $draftSong->songIdentifier(),
            $draftSong->status(),
            null,
            $draftSong->name(),
            new \DateTimeImmutable(),
        );

        // スナップショット（公開済みSongからスナップショットを作成）
        $snapshot = new SongSnapshot(
            new SongSnapshotIdentifier(StrTestHelper::generateUuid()),
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $language,
            $publishedName,
            $publishedAgencyIdentifier,
            $publishedGroupIdentifier,
            $publishedTalentIdentifier,
            $publishedLyricist,
            $publishedComposer,
            $publishedReleaseDate,
            $publishedOverView,
            $publishedCoverImagePath,
            $publishedMusicVideoLink,
            $publishedVersion,
            new DateTimeImmutable(),
        );

        return new PublishSongTestData(
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
            $draftSong,
            $publishedSong,
            $createdSong,
            $version,
            $publishedVersion,
            $historyIdentifier,
            $history,
            $snapshot,
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
        public DraftSong                $draftSong,
        public Song                     $publishedSong,
        public Song                     $createdSong,
        public Version                  $version,
        public Version                  $publishedVersion,
        public SongHistoryIdentifier    $historyIdentifier,
        public SongHistory              $history,
        public SongSnapshot             $snapshot,
    ) {
    }
}

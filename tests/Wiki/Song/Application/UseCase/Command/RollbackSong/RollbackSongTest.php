<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\RollbackSong;

use DateTimeImmutable;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidRollbackTargetVersionException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\SnapshotNotFoundException;
use Source\Wiki\Shared\Domain\Exception\VersionMismatchException;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\UseCase\Command\RollbackSong\RollbackSong;
use Source\Wiki\Song\Application\UseCase\Command\RollbackSong\RollbackSongInput;
use Source\Wiki\Song\Application\UseCase\Command\RollbackSong\RollbackSongInterface;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\Entity\SongSnapshot;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongSnapshotFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongSnapshotRepositoryInterface;
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

class RollbackSongTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songSnapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $songSnapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $songSnapshotRepository);
        $this->app->instance(SongSnapshotFactoryInterface::class, $songSnapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);

        $rollbackSong = $this->app->make(RollbackSongInterface::class);
        $this->assertInstanceOf(RollbackSong::class, $rollbackSong);
    }

    /**
     * 正常系: AdministratorがSongをロールバックできること（単一言語）.
     */
    public function testProcessWithAdministrator(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentSong = $this->createSong($songIdentifier, $translationSetIdentifier, $currentVersion);
        $snapshot = $this->createSnapshot($songIdentifier, $translationSetIdentifier, $targetVersion);
        $history = $this->createHistory($principalIdentifier, $songIdentifier);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackSongInput($principalIdentifier, $songIdentifier, $targetVersion);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$songIdentifier))
            ->once()
            ->andReturn($currentSong);
        $songRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$currentSong]);
        $songRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier),
                Mockery::on(fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([$snapshot]);
        $snapshotRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $newSnapshot = $this->createSnapshot($songIdentifier, $translationSetIdentifier, new Version(6));
        $snapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->once()
            ->andReturn($newSnapshot);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($history);

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(SongSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);

        $rollbackSong = $this->app->make(RollbackSongInterface::class);
        $result = $rollbackSong->process($input);

        $this->assertCount(1, $result);
        $this->assertSame((string)$songIdentifier, (string)$result[0]->songIdentifier());
        // バージョンがインクリメントされていること
        $this->assertSame(6, $result[0]->version()->value());
        // スナップショットの値に復元されていること
        $this->assertSame((string)$snapshot->name(), (string)$result[0]->name());
        $this->assertSame((string)$snapshot->lyricist(), (string)$result[0]->lyricist());
        $this->assertSame((string)$snapshot->composer(), (string)$result[0]->composer());
        $this->assertSame((string)$snapshot->overView(), (string)$result[0]->overView());
    }

    /**
     * 正常系: 複数言語のSongをロールバックできること.
     */
    public function testProcessWithMultipleLanguages(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $songIdentifierKo = new SongIdentifier(StrTestHelper::generateUuid());
        $songIdentifierJa = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $songKo = $this->createSong($songIdentifierKo, $translationSetIdentifier, $currentVersion, Language::KOREAN);
        $songJa = $this->createSong($songIdentifierJa, $translationSetIdentifier, $currentVersion, Language::JAPANESE);
        $snapshotKo = $this->createSnapshot($songIdentifierKo, $translationSetIdentifier, $targetVersion, Language::KOREAN);
        $snapshotJa = $this->createSnapshot($songIdentifierJa, $translationSetIdentifier, $targetVersion, Language::JAPANESE);
        $historyKo = $this->createHistory($principalIdentifier, $songIdentifierKo);
        $historyJa = $this->createHistory($principalIdentifier, $songIdentifierJa);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackSongInput($principalIdentifier, $songIdentifierKo, $targetVersion);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$songIdentifierKo))
            ->once()
            ->andReturn($songKo);
        $songRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$songKo, $songJa]);
        $songRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier),
                Mockery::on(fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([$snapshotKo, $snapshotJa]);
        $snapshotRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $newSnapshotKo = $this->createSnapshot($songIdentifierKo, $translationSetIdentifier, new Version(6), Language::KOREAN);
        $newSnapshotJa = $this->createSnapshot($songIdentifierJa, $translationSetIdentifier, new Version(6), Language::JAPANESE);
        $snapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function ($song) use ($newSnapshotKo, $newSnapshotJa, $songIdentifierKo) {
                if ((string)$song->songIdentifier() === (string)$songIdentifierKo) {
                    return $newSnapshotKo;
                }

                return $newSnapshotJa;
            });

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryFactory->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function ($editorIdentifier, $submitterIdentifier, $songIdentifier) use ($historyKo, $historyJa, $songIdentifierKo) {
                if ((string)$songIdentifier === (string)$songIdentifierKo) {
                    return $historyKo;
                }

                return $historyJa;
            });

        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);
        $songHistoryRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(SongSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);

        $rollbackSong = $this->app->make(RollbackSongInterface::class);
        $result = $rollbackSong->process($input);

        $this->assertCount(2, $result);
    }

    /**
     * 異常系: Songが存在しない場合、例外がスローされること.
     */
    public function testProcessThrowsSongNotFoundException(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);

        $input = new RollbackSongInput($principalIdentifier, $songIdentifier, $targetVersion);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$songIdentifier))
            ->once()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(SongSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);

        $rollbackSong = $this->app->make(RollbackSongInterface::class);

        $this->expectException(SongNotFoundException::class);
        $rollbackSong->process($input);
    }

    /**
     * 異常系: Principalが存在しない場合、例外がスローされること.
     */
    public function testProcessThrowsPrincipalNotFoundException(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentSong = $this->createSong($songIdentifier, $translationSetIdentifier, $currentVersion);

        $input = new RollbackSongInput($principalIdentifier, $songIdentifier, $targetVersion);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$songIdentifier))
            ->once()
            ->andReturn($currentSong);

        $snapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn(null);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(SongSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);

        $rollbackSong = $this->app->make(RollbackSongInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $rollbackSong->process($input);
    }

    /**
     * 異常系: SENIOR_COLLABORATORがロールバックを実行しようとすると、例外がスローされること.
     */
    public function testProcessThrowsDisallowedExceptionForSeniorCollaborator(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentSong = $this->createSong($songIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::SENIOR_COLLABORATOR,
            null,
            [],
            []
        );

        $input = new RollbackSongInput($principalIdentifier, $songIdentifier, $targetVersion);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$songIdentifier))
            ->once()
            ->andReturn($currentSong);

        $snapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(SongSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);

        $rollbackSong = $this->app->make(RollbackSongInterface::class);

        $this->expectException(DisallowedException::class);
        $rollbackSong->process($input);
    }

    /**
     * 異常系: targetVersionが現在のバージョン以上の場合、例外がスローされること.
     */
    public function testProcessThrowsInvalidRollbackTargetVersionException(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $currentVersion = new Version(5);
        $targetVersion = new Version(5); // 現在のバージョンと同じ

        $currentSong = $this->createSong($songIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackSongInput($principalIdentifier, $songIdentifier, $targetVersion);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$songIdentifier))
            ->once()
            ->andReturn($currentSong);

        $snapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(SongSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);

        $rollbackSong = $this->app->make(RollbackSongInterface::class);

        $this->expectException(InvalidRollbackTargetVersionException::class);
        $rollbackSong->process($input);
    }

    /**
     * 異常系: 翻訳セット内でバージョンが一致しない場合、例外がスローされること.
     */
    public function testProcessThrowsVersionMismatchException(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $songIdentifierKo = new SongIdentifier(StrTestHelper::generateUuid());
        $songIdentifierJa = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);

        // 異なるバージョンを持つSong
        $songKo = $this->createSong($songIdentifierKo, $translationSetIdentifier, new Version(5), Language::KOREAN);
        $songJa = $this->createSong($songIdentifierJa, $translationSetIdentifier, new Version(4), Language::JAPANESE);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackSongInput($principalIdentifier, $songIdentifierKo, $targetVersion);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$songIdentifierKo))
            ->once()
            ->andReturn($songKo);
        $songRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$songKo, $songJa]);

        $snapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(SongSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);

        $rollbackSong = $this->app->make(RollbackSongInterface::class);

        $this->expectException(VersionMismatchException::class);
        $rollbackSong->process($input);
    }

    /**
     * 異常系: スナップショットが存在しない場合、例外がスローされること.
     */
    public function testProcessThrowsSnapshotNotFoundException(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentSong = $this->createSong($songIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackSongInput($principalIdentifier, $songIdentifier, $targetVersion);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$songIdentifier))
            ->once()
            ->andReturn($currentSong);
        $songRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$currentSong]);

        $snapshotRepository = Mockery::mock(SongSnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier),
                Mockery::on(fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([]); // 空配列を返す

        $snapshotFactory = Mockery::mock(SongSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $songHistoryFactory = Mockery::mock(SongHistoryFactoryInterface::class);
        $songHistoryRepository = Mockery::mock(SongHistoryRepositoryInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(SongSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(SongHistoryFactoryInterface::class, $songHistoryFactory);
        $this->app->instance(SongHistoryRepositoryInterface::class, $songHistoryRepository);

        $rollbackSong = $this->app->make(RollbackSongInterface::class);

        $this->expectException(SnapshotNotFoundException::class);
        $rollbackSong->process($input);
    }

    private function createSong(
        SongIdentifier $songIdentifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN,
    ): Song {
        return new Song(
            $songIdentifier,
            $translationSetIdentifier,
            $language,
            new SongName('Current Name'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new Lyricist('Current Lyricist'),
            new Composer('Current Composer'),
            new ReleaseDate(new DateTimeImmutable('2024-01-01')),
            new Overview('Current Overview'),
            new ImagePath('/current/path.webp'),
            new ExternalContentLink('https://example.com/current'),
            $version,
        );
    }

    private function createSnapshot(
        SongIdentifier $songIdentifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN,
    ): SongSnapshot {
        return new SongSnapshot(
            new SongSnapshotIdentifier(StrTestHelper::generateUuid()),
            $songIdentifier,
            $translationSetIdentifier,
            $language,
            new SongName('Snapshot Name'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new Lyricist('Snapshot Lyricist'),
            new Composer('Snapshot Composer'),
            new ReleaseDate(new DateTimeImmutable('2023-06-01')),
            new Overview('Snapshot Overview'),
            new ImagePath('/snapshot/path.webp'),
            new ExternalContentLink('https://example.com/snapshot'),
            $version,
            new DateTimeImmutable('2024-01-01 00:00:00'),
        );
    }

    private function createHistory(
        PrincipalIdentifier $principalIdentifier,
        SongIdentifier $songIdentifier,
    ): SongHistory {
        return new SongHistory(
            new SongHistoryIdentifier(StrTestHelper::generateUuid()),
            $principalIdentifier,
            null,
            $songIdentifier,
            null,
            null,
            null,
            new SongName('Song Name'),
            new DateTimeImmutable('2024-01-01 00:00:00'),
        );
    }
}

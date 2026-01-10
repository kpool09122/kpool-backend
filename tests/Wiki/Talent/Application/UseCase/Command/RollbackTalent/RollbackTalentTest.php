<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\RollbackTalent;

use DateTimeImmutable;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidRollbackTargetVersionException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\SnapshotNotFoundException;
use Source\Wiki\Shared\Domain\Exception\VersionMismatchException;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\UseCase\Command\RollbackTalent\RollbackTalent;
use Source\Wiki\Talent\Application\UseCase\Command\RollbackTalent\RollbackTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\RollbackTalent\RollbackTalentInterface;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Entity\TalentHistory;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\Factory\TalentSnapshotFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentHistoryRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentSnapshotRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackTalentTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentSnapshotRepository = Mockery::mock(TalentSnapshotRepositoryInterface::class);
        $talentSnapshotFactory = Mockery::mock(TalentSnapshotFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, $talentSnapshotRepository);
        $this->app->instance(TalentSnapshotFactoryInterface::class, $talentSnapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);

        $rollbackTalent = $this->app->make(RollbackTalentInterface::class);
        $this->assertInstanceOf(RollbackTalent::class, $rollbackTalent);
    }

    /**
     * 正常系: AdministratorがTalentをロールバックできること（単一言語）.
     */
    public function testProcessWithAdministrator(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentTalent = $this->createTalent($talentIdentifier, $translationSetIdentifier, $currentVersion);
        $snapshot = $this->createSnapshot($talentIdentifier, $translationSetIdentifier, $targetVersion);
        $history = $this->createHistory($principalIdentifier, $talentIdentifier);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackTalentInput($principalIdentifier, $talentIdentifier, $targetVersion);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$talentIdentifier))
            ->once()
            ->andReturn($currentTalent);
        $talentRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$currentTalent]);
        $talentRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(TalentSnapshotRepositoryInterface::class);
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

        $newSnapshot = $this->createSnapshot($talentIdentifier, $translationSetIdentifier, new Version(6));
        $snapshotFactory = Mockery::mock(TalentSnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->once()
            ->andReturn($newSnapshot);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($history);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(TalentSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);

        $rollbackTalent = $this->app->make(RollbackTalentInterface::class);
        $result = $rollbackTalent->process($input);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Talent::class, $result[0]);
    }

    /**
     * 異常系: Talentが存在しない場合に例外がスローされること.
     */
    public function testProcessThrowsTalentNotFoundException(): void
    {
        $this->expectException(TalentNotFoundException::class);

        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);

        $input = new RollbackTalentInput($principalIdentifier, $talentIdentifier, $targetVersion);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, Mockery::mock(TalentSnapshotRepositoryInterface::class));
        $this->app->instance(TalentSnapshotFactoryInterface::class, Mockery::mock(TalentSnapshotFactoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));
        $this->app->instance(TalentHistoryFactoryInterface::class, Mockery::mock(TalentHistoryFactoryInterface::class));
        $this->app->instance(TalentHistoryRepositoryInterface::class, Mockery::mock(TalentHistoryRepositoryInterface::class));

        $rollbackTalent = $this->app->make(RollbackTalentInterface::class);
        $rollbackTalent->process($input);
    }

    /**
     * 異常系: Principalが存在しない場合に例外がスローされること.
     */
    public function testProcessThrowsPrincipalNotFoundException(): void
    {
        $this->expectException(PrincipalNotFoundException::class);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentTalent = $this->createTalent($talentIdentifier, $translationSetIdentifier, $currentVersion);
        $input = new RollbackTalentInput($principalIdentifier, $talentIdentifier, $targetVersion);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($currentTalent);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, Mockery::mock(TalentSnapshotRepositoryInterface::class));
        $this->app->instance(TalentSnapshotFactoryInterface::class, Mockery::mock(TalentSnapshotFactoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, Mockery::mock(TalentHistoryFactoryInterface::class));
        $this->app->instance(TalentHistoryRepositoryInterface::class, Mockery::mock(TalentHistoryRepositoryInterface::class));

        $rollbackTalent = $this->app->make(RollbackTalentInterface::class);
        $rollbackTalent->process($input);
    }

    /**
     * 異常系: 権限がない場合に例外がスローされること.
     */
    public function testProcessThrowsDisallowedException(): void
    {
        $this->expectException(DisallowedException::class);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentTalent = $this->createTalent($talentIdentifier, $translationSetIdentifier, $currentVersion);

        // SENIOR_COLLABORATORロールはDENY_ROLLBACKポリシーを持つため権限がない
        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackTalentInput($principalIdentifier, $talentIdentifier, $targetVersion);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($currentTalent);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->andReturn($principal);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, Mockery::mock(TalentSnapshotRepositoryInterface::class));
        $this->app->instance(TalentSnapshotFactoryInterface::class, Mockery::mock(TalentSnapshotFactoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, Mockery::mock(TalentHistoryFactoryInterface::class));
        $this->app->instance(TalentHistoryRepositoryInterface::class, Mockery::mock(TalentHistoryRepositoryInterface::class));

        $this->setPolicyEvaluatorResult(false);
        $rollbackTalent = $this->app->make(RollbackTalentInterface::class);
        $rollbackTalent->process($input);
    }

    /**
     * 異常系: ロールバック対象バージョンが現在バージョン以上の場合に例外がスローされること.
     */
    public function testProcessThrowsInvalidRollbackTargetVersionException(): void
    {
        $this->expectException(InvalidRollbackTargetVersionException::class);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(5); // 現在と同じ
        $currentVersion = new Version(5);

        $currentTalent = $this->createTalent($talentIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackTalentInput($principalIdentifier, $talentIdentifier, $targetVersion);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($currentTalent);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->andReturn($principal);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, Mockery::mock(TalentSnapshotRepositoryInterface::class));
        $this->app->instance(TalentSnapshotFactoryInterface::class, Mockery::mock(TalentSnapshotFactoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, Mockery::mock(TalentHistoryFactoryInterface::class));
        $this->app->instance(TalentHistoryRepositoryInterface::class, Mockery::mock(TalentHistoryRepositoryInterface::class));

        $rollbackTalent = $this->app->make(RollbackTalentInterface::class);
        $rollbackTalent->process($input);
    }

    /**
     * 異常系: 翻訳セット内でバージョンが一致しない場合に例外がスローされること.
     */
    public function testProcessThrowsVersionMismatchException(): void
    {
        $this->expectException(VersionMismatchException::class);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier1 = new TalentIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier2 = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);
        $differentVersion = new Version(4); // バージョン不一致

        $currentTalent1 = $this->createTalent($talentIdentifier1, $translationSetIdentifier, $currentVersion);
        $currentTalent2 = $this->createTalent($talentIdentifier2, $translationSetIdentifier, $differentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackTalentInput($principalIdentifier, $talentIdentifier1, $targetVersion);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($currentTalent1);
        $talentRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->andReturn([$currentTalent1, $currentTalent2]);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->andReturn($principal);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, Mockery::mock(TalentSnapshotRepositoryInterface::class));
        $this->app->instance(TalentSnapshotFactoryInterface::class, Mockery::mock(TalentSnapshotFactoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, Mockery::mock(TalentHistoryFactoryInterface::class));
        $this->app->instance(TalentHistoryRepositoryInterface::class, Mockery::mock(TalentHistoryRepositoryInterface::class));

        $rollbackTalent = $this->app->make(RollbackTalentInterface::class);
        $rollbackTalent->process($input);
    }

    /**
     * 異常系: スナップショットが存在しない場合に例外がスローされること.
     */
    public function testProcessThrowsSnapshotNotFoundException(): void
    {
        $this->expectException(SnapshotNotFoundException::class);

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentTalent = $this->createTalent($talentIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackTalentInput($principalIdentifier, $talentIdentifier, $targetVersion);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($currentTalent);
        $talentRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->andReturn([$currentTalent]);

        $snapshotRepository = Mockery::mock(TalentSnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->once()
            ->andReturn([]); // スナップショットなし

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->andReturn($principal);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(TalentSnapshotFactoryInterface::class, Mockery::mock(TalentSnapshotFactoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, Mockery::mock(TalentHistoryFactoryInterface::class));
        $this->app->instance(TalentHistoryRepositoryInterface::class, Mockery::mock(TalentHistoryRepositoryInterface::class));

        $rollbackTalent = $this->app->make(RollbackTalentInterface::class);
        $rollbackTalent->process($input);
    }

    /**
     * 正常系: 複数言語のTalentを一括でロールバックできること.
     */
    public function testProcessWithMultipleLanguages(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier1 = new TalentIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier2 = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentTalent1 = $this->createTalent($talentIdentifier1, $translationSetIdentifier, $currentVersion, Language::KOREAN);
        $currentTalent2 = $this->createTalent($talentIdentifier2, $translationSetIdentifier, $currentVersion, Language::ENGLISH);
        $snapshot1 = $this->createSnapshot($talentIdentifier1, $translationSetIdentifier, $targetVersion, Language::KOREAN);
        $snapshot2 = $this->createSnapshot($talentIdentifier2, $translationSetIdentifier, $targetVersion, Language::ENGLISH);
        $history = $this->createHistory($principalIdentifier, $talentIdentifier1);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackTalentInput($principalIdentifier, $talentIdentifier1, $targetVersion);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($currentTalent1);
        $talentRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->andReturn([$currentTalent1, $currentTalent2]);
        $talentRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(TalentSnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->once()
            ->andReturn([$snapshot1, $snapshot2]);
        $snapshotRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $newSnapshot = $this->createSnapshot($talentIdentifier1, $translationSetIdentifier, new Version(6));
        $snapshotFactory = Mockery::mock(TalentSnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->twice()
            ->andReturn($newSnapshot);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->andReturn($principal);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->twice()
            ->andReturn($history);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(TalentSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);

        $rollbackTalent = $this->app->make(RollbackTalentInterface::class);
        $result = $rollbackTalent->process($input);

        $this->assertCount(2, $result);
    }

    private function createTalent(
        TalentIdentifier $talentIdentifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN
    ): Talent {
        return new Talent(
            $talentIdentifier,
            $translationSetIdentifier,
            $language,
            new TalentName('테스트'),
            new RealName('테스트 실명'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new GroupIdentifier(StrTestHelper::generateUuid())],
            new Birthday(new DateTimeImmutable('1999-01-01')),
            new Career('Test career'),
            new ImagePath('/images/test.jpg'),
            new RelevantVideoLinks([]),
            $version,
        );
    }

    private function createSnapshot(
        TalentIdentifier $talentIdentifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN
    ): TalentSnapshot {
        return new TalentSnapshot(
            new TalentSnapshotIdentifier(StrTestHelper::generateUuid()),
            $talentIdentifier,
            $translationSetIdentifier,
            $language,
            new TalentName('스냅샷'),
            new RealName('스냅샷 실명'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new GroupIdentifier(StrTestHelper::generateUuid())],
            new Birthday(new DateTimeImmutable('1999-01-01')),
            new Career('Snapshot career'),
            new ImagePath('/images/snapshot.jpg'),
            new RelevantVideoLinks([]),
            $version,
            new DateTimeImmutable(),
        );
    }

    private function createHistory(
        PrincipalIdentifier $principalIdentifier,
        TalentIdentifier $talentIdentifier
    ): TalentHistory {
        return new TalentHistory(
            new TalentHistoryIdentifier(StrTestHelper::generateUuid()),
            HistoryActionType::Rollback,
            $principalIdentifier,
            null,
            $talentIdentifier,
            null,
            null,
            null,
            new Version(5),
            new Version(2),
            new TalentName('테스트'),
            new DateTimeImmutable(),
        );
    }
}

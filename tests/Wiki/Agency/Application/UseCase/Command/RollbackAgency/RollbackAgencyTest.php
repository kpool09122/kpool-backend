<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\RollbackAgency;

use DateTimeImmutable;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\UseCase\Command\RollbackAgency\RollbackAgency;
use Source\Wiki\Agency\Application\UseCase\Command\RollbackAgency\RollbackAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\RollbackAgency\RollbackAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\AgencyHistory;
use Source\Wiki\Agency\Domain\Entity\AgencySnapshot;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\Factory\AgencySnapshotFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyHistoryRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencySnapshotRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyHistoryIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AgencySnapshotIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidRollbackTargetVersionException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\SnapshotNotFoundException;
use Source\Wiki\Shared\Domain\Exception\VersionMismatchException;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackAgencyTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencySnapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $agencySnapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $agencySnapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $agencySnapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $this->assertInstanceOf(RollbackAgency::class, $rollbackAgency);
    }

    /**
     * 正常系: AdministratorがAgencyをロールバックできること（単一言語）.
     */
    public function testProcessWithAdministrator(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentAgency = $this->createAgency($agencyIdentifier, $translationSetIdentifier, $currentVersion);
        $snapshot = $this->createSnapshot($agencyIdentifier, $translationSetIdentifier, $targetVersion);
        $history = $this->createHistory($principalIdentifier, $agencyIdentifier);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackAgencyInput($principalIdentifier, $agencyIdentifier, $targetVersion);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$agencyIdentifier))
            ->once()
            ->andReturn($currentAgency);
        $agencyRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$currentAgency]);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(static fn ($arg) => (string)$arg === (string)$translationSetIdentifier),
                Mockery::on(static fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([$snapshot]);
        $snapshotRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $newSnapshot = $this->createSnapshot($agencyIdentifier, $translationSetIdentifier, new Version(6));
        $snapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->once()
            ->andReturn($newSnapshot);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($history);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $result = $rollbackAgency->process($input);

        $this->assertCount(1, $result);
        $this->assertSame((string)$agencyIdentifier, (string)$result[0]->agencyIdentifier());
        $this->assertSame((string)$snapshot->name(), (string)$result[0]->name());
        $this->assertSame(6, $result[0]->version()->value());
    }

    /**
     * 正常系: 複数言語を同時にロールバックできること.
     */
    public function testProcessWithMultipleLanguages(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifierKo = new AgencyIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifierJa = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentAgencyKo = $this->createAgency($agencyIdentifierKo, $translationSetIdentifier, $currentVersion, Language::KOREAN);
        $currentAgencyJa = $this->createAgency($agencyIdentifierJa, $translationSetIdentifier, $currentVersion, Language::JAPANESE);
        $snapshotKo = $this->createSnapshot($agencyIdentifierKo, $translationSetIdentifier, $targetVersion, Language::KOREAN);
        $snapshotJa = $this->createSnapshot($agencyIdentifierJa, $translationSetIdentifier, $targetVersion, Language::JAPANESE);
        $historyKo = $this->createHistory($principalIdentifier, $agencyIdentifierKo);
        $historyJa = $this->createHistory($principalIdentifier, $agencyIdentifierJa);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackAgencyInput($principalIdentifier, $agencyIdentifierKo, $targetVersion);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$agencyIdentifierKo))
            ->once()
            ->andReturn($currentAgencyKo);
        $agencyRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$currentAgencyKo, $currentAgencyJa]);
        $agencyRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(static fn ($arg) => (string)$arg === (string)$translationSetIdentifier),
                Mockery::on(static fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([$snapshotKo, $snapshotJa]);
        $snapshotRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $newSnapshotKo = $this->createSnapshot($agencyIdentifierKo, $translationSetIdentifier, new Version(6), Language::KOREAN);
        $newSnapshotJa = $this->createSnapshot($agencyIdentifierJa, $translationSetIdentifier, new Version(6), Language::JAPANESE);
        $snapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function ($agency) use ($newSnapshotKo, $newSnapshotJa, $agencyIdentifierKo) {
                if ((string)$agency->agencyIdentifier() === (string)$agencyIdentifierKo) {
                    return $newSnapshotKo;
                }

                return $newSnapshotJa;
            });

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->twice()
            ->andReturn($historyKo, $historyJa);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $result = $rollbackAgency->process($input);

        $this->assertCount(2, $result);
    }

    /**
     * 異常系: SeniorCollaboratorはロールバックできないこと.
     */
    public function testProcessWithSeniorCollaboratorThrowsDisallowed(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentAgency = $this->createAgency($agencyIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackAgencyInput($principalIdentifier, $agencyIdentifier, $targetVersion);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$agencyIdentifier))
            ->once()
            ->andReturn($currentAgency);

        $snapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $this->expectException(DisallowedException::class);
        $this->setPolicyEvaluatorResult(false);
        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $rollbackAgency->process($input);
    }

    /**
     * 異常系: Collaboratorはロールバックできないこと.
     */
    public function testProcessWithCollaboratorThrowsDisallowed(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentAgency = $this->createAgency($agencyIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackAgencyInput($principalIdentifier, $agencyIdentifier, $targetVersion);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$agencyIdentifier))
            ->once()
            ->andReturn($currentAgency);

        $snapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $this->expectException(DisallowedException::class);
        $this->setPolicyEvaluatorResult(false);
        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $rollbackAgency->process($input);
    }

    /**
     * 異常系: Agencyが見つからない場合.
     */
    public function testWhenAgencyNotFound(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);

        $input = new RollbackAgencyInput($principalIdentifier, $agencyIdentifier, $targetVersion);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$agencyIdentifier))
            ->once()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $this->expectException(AgencyNotFoundException::class);
        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $rollbackAgency->process($input);
    }

    /**
     * 異常系: Principalが見つからない場合.
     */
    public function testWhenPrincipalNotFound(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentAgency = $this->createAgency($agencyIdentifier, $translationSetIdentifier, $currentVersion);

        $input = new RollbackAgencyInput($principalIdentifier, $agencyIdentifier, $targetVersion);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$agencyIdentifier))
            ->once()
            ->andReturn($currentAgency);

        $snapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn(null);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $rollbackAgency->process($input);
    }

    /**
     * 異常系: Snapshotが見つからない場合.
     */
    public function testWhenSnapshotNotFound(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentAgency = $this->createAgency($agencyIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackAgencyInput($principalIdentifier, $agencyIdentifier, $targetVersion);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$agencyIdentifier))
            ->once()
            ->andReturn($currentAgency);
        $agencyRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$currentAgency]);

        $snapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(static fn ($arg) => (string)$arg === (string)$translationSetIdentifier),
                Mockery::on(static fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([]);

        $snapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $this->expectException(SnapshotNotFoundException::class);
        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $rollbackAgency->process($input);
    }

    /**
     * 異常系: 翻訳セット内でバージョン不一致の場合.
     */
    public function testWhenVersionMismatch(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifierKo = new AgencyIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifierJa = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);

        $currentAgencyKo = $this->createAgency($agencyIdentifierKo, $translationSetIdentifier, new Version(5), Language::KOREAN);
        $currentAgencyJa = $this->createAgency($agencyIdentifierJa, $translationSetIdentifier, new Version(4), Language::JAPANESE);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackAgencyInput($principalIdentifier, $agencyIdentifierKo, $targetVersion);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$agencyIdentifierKo))
            ->once()
            ->andReturn($currentAgencyKo);
        $agencyRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$currentAgencyKo, $currentAgencyJa]);

        $snapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $this->expectException(VersionMismatchException::class);
        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $rollbackAgency->process($input);
    }

    /**
     * 異常系: targetVersionが現在のバージョン以上の場合.
     */
    public function testWhenInvalidRollbackTargetVersion(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(5);
        $currentVersion = new Version(5);

        $currentAgency = $this->createAgency($agencyIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackAgencyInput($principalIdentifier, $agencyIdentifier, $targetVersion);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$agencyIdentifier))
            ->once()
            ->andReturn($currentAgency);

        $snapshotRepository = Mockery::mock(AgencySnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(AgencySnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencySnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(AgencySnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);

        $this->expectException(InvalidRollbackTargetVersionException::class);
        $rollbackAgency = $this->app->make(RollbackAgencyInterface::class);
        $rollbackAgency->process($input);
    }

    private function createAgency(
        AgencyIdentifier $identifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN
    ): Agency {
        return new Agency(
            $identifier,
            $translationSetIdentifier,
            $language,
            new AgencyName('Test Agency'),
            'test agency',
            new CEO('Test CEO'),
            'test ceo',
            new FoundedIn(new DateTimeImmutable('2020-01-01')),
            new Description('Test Description'),
            $version,
        );
    }

    private function createSnapshot(
        AgencyIdentifier $identifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN
    ): AgencySnapshot {
        return new AgencySnapshot(
            new AgencySnapshotIdentifier(StrTestHelper::generateUuid()),
            $identifier,
            $translationSetIdentifier,
            $language,
            new AgencyName('Snapshot Agency Name'),
            'snapshot agency name',
            new CEO('Snapshot CEO'),
            'snapshot ceo',
            new FoundedIn(new DateTimeImmutable('2019-01-01')),
            new Description('Snapshot Description'),
            $version,
            new DateTimeImmutable(),
        );
    }

    private function createHistory(
        PrincipalIdentifier $editorIdentifier,
        AgencyIdentifier $agencyIdentifier,
    ): AgencyHistory {
        return new AgencyHistory(
            new AgencyHistoryIdentifier(StrTestHelper::generateUuid()),
            HistoryActionType::Rollback,
            $editorIdentifier,
            null,
            $agencyIdentifier,
            null,
            null,
            null,
            new Version(5),
            new Version(2),
            new AgencyName('Test Agency'),
            new DateTimeImmutable(),
        );
    }
}

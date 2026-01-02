<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\RollbackGroup;

use DateTimeImmutable;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\UseCase\Command\RollbackGroup\RollbackGroup;
use Source\Wiki\Group\Application\UseCase\Command\RollbackGroup\RollbackGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\RollbackGroup\RollbackGroupInterface;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Entity\GroupHistory;
use Source\Wiki\Group\Domain\Entity\GroupSnapshot;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupSnapshotFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupHistoryRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupSnapshotRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupHistoryIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidRollbackTargetVersionException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\SnapshotNotFoundException;
use Source\Wiki\Shared\Domain\Exception\VersionMismatchException;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackGroupTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupSnapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $groupSnapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $groupSnapshotRepository);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $groupSnapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);

        $rollbackGroup = $this->app->make(RollbackGroupInterface::class);
        $this->assertInstanceOf(RollbackGroup::class, $rollbackGroup);
    }

    /**
     * 正常系: AdministratorがGroupをロールバックできること（単一言語）.
     */
    public function testProcessWithAdministrator(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentGroup = $this->createGroup($groupIdentifier, $translationSetIdentifier, $currentVersion);
        $snapshot = $this->createSnapshot($groupIdentifier, $translationSetIdentifier, $targetVersion);
        $history = $this->createHistory($principalIdentifier, $groupIdentifier);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackGroupInput($principalIdentifier, $groupIdentifier, $targetVersion);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$groupIdentifier))
            ->once()
            ->andReturn($currentGroup);
        $groupRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$currentGroup]);
        $groupRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
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

        $newSnapshot = $this->createSnapshot($groupIdentifier, $translationSetIdentifier, new Version(6));
        $snapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->once()
            ->andReturn($newSnapshot);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($history);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);

        $rollbackGroup = $this->app->make(RollbackGroupInterface::class);
        $result = $rollbackGroup->process($input);

        $this->assertCount(1, $result);
        $this->assertSame((string)$groupIdentifier, (string)$result[0]->groupIdentifier());
        // バージョンがインクリメントされていること
        $this->assertSame(6, $result[0]->version()->value());
        // スナップショットの値に復元されていること
        $this->assertSame((string)$snapshot->name(), (string)$result[0]->name());
        $this->assertSame($snapshot->normalizedName(), $result[0]->normalizedName());
        $this->assertSame((string)$snapshot->description(), (string)$result[0]->description());
    }

    /**
     * 正常系: 複数言語のGroupをロールバックできること.
     */
    public function testProcessWithMultipleLanguages(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $groupIdentifierKo = new GroupIdentifier(StrTestHelper::generateUuid());
        $groupIdentifierJa = new GroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $groupKo = $this->createGroup($groupIdentifierKo, $translationSetIdentifier, $currentVersion, Language::KOREAN);
        $groupJa = $this->createGroup($groupIdentifierJa, $translationSetIdentifier, $currentVersion, Language::JAPANESE);
        $snapshotKo = $this->createSnapshot($groupIdentifierKo, $translationSetIdentifier, $targetVersion, Language::KOREAN);
        $snapshotJa = $this->createSnapshot($groupIdentifierJa, $translationSetIdentifier, $targetVersion, Language::JAPANESE);
        $historyKo = $this->createHistory($principalIdentifier, $groupIdentifierKo);
        $historyJa = $this->createHistory($principalIdentifier, $groupIdentifierJa);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackGroupInput($principalIdentifier, $groupIdentifierKo, $targetVersion);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$groupIdentifierKo))
            ->once()
            ->andReturn($groupKo);
        $groupRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$groupKo, $groupJa]);
        $groupRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
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

        $newSnapshotKo = $this->createSnapshot($groupIdentifierKo, $translationSetIdentifier, new Version(6), Language::KOREAN);
        $newSnapshotJa = $this->createSnapshot($groupIdentifierJa, $translationSetIdentifier, new Version(6), Language::JAPANESE);
        $snapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function ($group) use ($newSnapshotKo, $newSnapshotJa, $groupIdentifierKo) {
                if ((string)$group->groupIdentifier() === (string)$groupIdentifierKo) {
                    return $newSnapshotKo;
                }

                return $newSnapshotJa;
            });

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function ($actionType, $editorIdentifier, $submitterIdentifier, $groupIdentifier) use ($historyKo, $historyJa, $groupIdentifierKo) {
                if ((string)$groupIdentifier === (string)$groupIdentifierKo) {
                    return $historyKo;
                }

                return $historyJa;
            });

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);

        $rollbackGroup = $this->app->make(RollbackGroupInterface::class);
        $result = $rollbackGroup->process($input);

        $this->assertCount(2, $result);
    }

    /**
     * 異常系: Groupが存在しない場合、例外がスローされること.
     */
    public function testProcessThrowsGroupNotFoundException(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);

        $input = new RollbackGroupInput($principalIdentifier, $groupIdentifier, $targetVersion);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$groupIdentifier))
            ->once()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);

        $rollbackGroup = $this->app->make(RollbackGroupInterface::class);

        $this->expectException(GroupNotFoundException::class);
        $rollbackGroup->process($input);
    }

    /**
     * 異常系: Principalが存在しない場合、例外がスローされること.
     */
    public function testProcessThrowsPrincipalNotFoundException(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentGroup = $this->createGroup($groupIdentifier, $translationSetIdentifier, $currentVersion);

        $input = new RollbackGroupInput($principalIdentifier, $groupIdentifier, $targetVersion);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$groupIdentifier))
            ->once()
            ->andReturn($currentGroup);

        $snapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn(null);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);

        $rollbackGroup = $this->app->make(RollbackGroupInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $rollbackGroup->process($input);
    }

    /**
     * 異常系: SENIOR_COLLABORATORがロールバックを実行しようとすると、例外がスローされること.
     */
    public function testProcessThrowsDisallowedExceptionForSeniorCollaborator(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentGroup = $this->createGroup($groupIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::SENIOR_COLLABORATOR,
            null,
            [],
            []
        );

        $input = new RollbackGroupInput($principalIdentifier, $groupIdentifier, $targetVersion);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$groupIdentifier))
            ->once()
            ->andReturn($currentGroup);

        $snapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);

        $rollbackGroup = $this->app->make(RollbackGroupInterface::class);

        $this->expectException(DisallowedException::class);
        $rollbackGroup->process($input);
    }

    /**
     * 異常系: targetVersionが現在のバージョン以上の場合、例外がスローされること.
     */
    public function testProcessThrowsInvalidRollbackTargetVersionException(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $currentVersion = new Version(5);
        $targetVersion = new Version(5); // 現在のバージョンと同じ

        $currentGroup = $this->createGroup($groupIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackGroupInput($principalIdentifier, $groupIdentifier, $targetVersion);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$groupIdentifier))
            ->once()
            ->andReturn($currentGroup);

        $snapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);

        $rollbackGroup = $this->app->make(RollbackGroupInterface::class);

        $this->expectException(InvalidRollbackTargetVersionException::class);
        $rollbackGroup->process($input);
    }

    /**
     * 異常系: 翻訳セット内でバージョンが一致しない場合、例外がスローされること.
     */
    public function testProcessThrowsVersionMismatchException(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $groupIdentifierKo = new GroupIdentifier(StrTestHelper::generateUuid());
        $groupIdentifierJa = new GroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);

        // 異なるバージョンを持つGroup
        $groupKo = $this->createGroup($groupIdentifierKo, $translationSetIdentifier, new Version(5), Language::KOREAN);
        $groupJa = $this->createGroup($groupIdentifierJa, $translationSetIdentifier, new Version(4), Language::JAPANESE);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackGroupInput($principalIdentifier, $groupIdentifierKo, $targetVersion);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$groupIdentifierKo))
            ->once()
            ->andReturn($groupKo);
        $groupRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$groupKo, $groupJa]);

        $snapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);

        $rollbackGroup = $this->app->make(RollbackGroupInterface::class);

        $this->expectException(VersionMismatchException::class);
        $rollbackGroup->process($input);
    }

    /**
     * 異常系: スナップショットが存在しない場合、例外がスローされること.
     */
    public function testProcessThrowsSnapshotNotFoundException(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentGroup = $this->createGroup($groupIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            []
        );

        $input = new RollbackGroupInput($principalIdentifier, $groupIdentifier, $targetVersion);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$groupIdentifier))
            ->once()
            ->andReturn($currentGroup);
        $groupRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier))
            ->once()
            ->andReturn([$currentGroup]);

        $snapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(fn ($arg) => (string)$arg === (string)$translationSetIdentifier),
                Mockery::on(fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([]); // 空配列を返す

        $snapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn ($arg) => (string)$arg === (string)$principalIdentifier))
            ->once()
            ->andReturn($principal);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);

        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);

        $rollbackGroup = $this->app->make(RollbackGroupInterface::class);

        $this->expectException(SnapshotNotFoundException::class);
        $rollbackGroup->process($input);
    }

    private function createGroup(
        GroupIdentifier $groupIdentifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN,
    ): Group {
        return new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $language,
            new GroupName('Current Name'),
            'current name',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('Current Description'),
            new ImagePath('/current/path.webp'),
            $version,
        );
    }

    private function createSnapshot(
        GroupIdentifier $groupIdentifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN,
    ): GroupSnapshot {
        return new GroupSnapshot(
            new GroupSnapshotIdentifier(StrTestHelper::generateUuid()),
            $groupIdentifier,
            $translationSetIdentifier,
            $language,
            new GroupName('Snapshot Name'),
            'snapshot name',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('Snapshot Description'),
            new ImagePath('/snapshot/path.webp'),
            $version,
            new DateTimeImmutable('2024-01-01 00:00:00'),
        );
    }

    private function createHistory(
        PrincipalIdentifier $principalIdentifier,
        GroupIdentifier $groupIdentifier,
    ): GroupHistory {
        return new GroupHistory(
            new GroupHistoryIdentifier(StrTestHelper::generateUuid()),
            HistoryActionType::Rollback,
            $principalIdentifier,
            null,
            $groupIdentifier,
            null,
            null,
            null,
            new Version(5),
            new Version(2),
            new GroupName('Group Name'),
            new DateTimeImmutable('2024-01-01 00:00:00'),
        );
    }
}

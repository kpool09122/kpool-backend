<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\RollbackWiki;

use DateTimeImmutable;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
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
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiInterface;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Factory\WikiSnapshotFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiSnapshotIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackWikiTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $snapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        $rollbackWiki = $this->app->make(RollbackWikiInterface::class);
        $this->assertInstanceOf(RollbackWiki::class, $rollbackWiki);
    }

    /**
     * 正常系: Wikiをロールバックできること（単一言語）.
     */
    public function testProcessWithSingleLanguage(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentWiki = $this->createWiki($wikiIdentifier, $translationSetIdentifier, $currentVersion);
        $snapshot = $this->createSnapshot($wikiIdentifier, $translationSetIdentifier, $targetVersion);
        $history = $this->createHistory($principalIdentifier, $wikiIdentifier);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackWikiInput(
            $principalIdentifier,
            $wikiIdentifier,
            $targetVersion,
            ResourceType::GROUP,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $wikiIdentifier))
            ->once()
            ->andReturn($currentWiki);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $translationSetIdentifier))
            ->once()
            ->andReturn([$currentWiki]);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(static fn ($arg) => (string) $arg === (string) $translationSetIdentifier),
                Mockery::on(static fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([$snapshot]);
        $snapshotRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $newSnapshot = $this->createSnapshot($wikiIdentifier, $translationSetIdentifier, new Version(6));
        $snapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->once()
            ->andReturn($newSnapshot);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $principalIdentifier))
            ->once()
            ->andReturn($principal);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($history);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        $rollbackWiki = $this->app->make(RollbackWikiInterface::class);
        $result = $rollbackWiki->process($input);

        $this->assertCount(1, $result);
        $this->assertSame((string) $wikiIdentifier, (string) $result[0]->wikiIdentifier());
        $this->assertSame((string) $snapshot->basic()->name(), (string) $result[0]->basic()->name());
        $this->assertSame(6, $result[0]->version()->value());
    }

    /**
     * 正常系: 複数言語を同時にロールバックできること.
     */
    public function testProcessWithMultipleLanguages(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifierKo = new WikiIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifierJa = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentWikiKo = $this->createWiki($wikiIdentifierKo, $translationSetIdentifier, $currentVersion, Language::KOREAN);
        $currentWikiJa = $this->createWiki($wikiIdentifierJa, $translationSetIdentifier, $currentVersion, Language::JAPANESE);
        $snapshotKo = $this->createSnapshot($wikiIdentifierKo, $translationSetIdentifier, $targetVersion, Language::KOREAN);
        $snapshotJa = $this->createSnapshot($wikiIdentifierJa, $translationSetIdentifier, $targetVersion, Language::JAPANESE);
        $historyKo = $this->createHistory($principalIdentifier, $wikiIdentifierKo);
        $historyJa = $this->createHistory($principalIdentifier, $wikiIdentifierJa);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackWikiInput(
            $principalIdentifier,
            $wikiIdentifierKo,
            $targetVersion,
            ResourceType::GROUP,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $wikiIdentifierKo))
            ->once()
            ->andReturn($currentWikiKo);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $translationSetIdentifier))
            ->once()
            ->andReturn([$currentWikiKo, $currentWikiJa]);
        $wikiRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(static fn ($arg) => (string) $arg === (string) $translationSetIdentifier),
                Mockery::on(static fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([$snapshotKo, $snapshotJa]);
        $snapshotRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $newSnapshotKo = $this->createSnapshot($wikiIdentifierKo, $translationSetIdentifier, new Version(6), Language::KOREAN);
        $newSnapshotJa = $this->createSnapshot($wikiIdentifierJa, $translationSetIdentifier, new Version(6), Language::JAPANESE);
        $snapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $snapshotFactory->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function ($wiki) use ($newSnapshotKo, $newSnapshotJa, $wikiIdentifierKo) {
                if ((string) $wiki->wikiIdentifier() === (string) $wikiIdentifierKo) {
                    return $newSnapshotKo;
                }

                return $newSnapshotJa;
            });

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $principalIdentifier))
            ->once()
            ->andReturn($principal);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->twice()
            ->andReturn($historyKo, $historyJa);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        $rollbackWiki = $this->app->make(RollbackWikiInterface::class);
        $result = $rollbackWiki->process($input);

        $this->assertCount(2, $result);
    }

    /**
     * 異常系: ロールバック権限がない場合.
     */
    public function testProcessWithDisallowedThrowsException(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentWiki = $this->createWiki($wikiIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackWikiInput(
            $principalIdentifier,
            $wikiIdentifier,
            $targetVersion,
            ResourceType::GROUP,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $wikiIdentifier))
            ->once()
            ->andReturn($currentWiki);

        $snapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $principalIdentifier))
            ->once()
            ->andReturn($principal);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        $this->expectException(DisallowedException::class);
        $this->setPolicyEvaluatorResult(false);
        $rollbackWiki = $this->app->make(RollbackWikiInterface::class);
        $rollbackWiki->process($input);
    }

    /**
     * 異常系: Wikiが見つからない場合.
     */
    public function testWhenWikiNotFound(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);

        $input = new RollbackWikiInput(
            $principalIdentifier,
            $wikiIdentifier,
            $targetVersion,
            ResourceType::GROUP,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $wikiIdentifier))
            ->once()
            ->andReturn(null);

        $snapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        $this->expectException(WikiNotFoundException::class);
        $rollbackWiki = $this->app->make(RollbackWikiInterface::class);
        $rollbackWiki->process($input);
    }

    /**
     * 異常系: Principalが見つからない場合.
     */
    public function testWhenPrincipalNotFound(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentWiki = $this->createWiki($wikiIdentifier, $translationSetIdentifier, $currentVersion);

        $input = new RollbackWikiInput(
            $principalIdentifier,
            $wikiIdentifier,
            $targetVersion,
            ResourceType::GROUP,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $wikiIdentifier))
            ->once()
            ->andReturn($currentWiki);

        $snapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $principalIdentifier))
            ->once()
            ->andReturn(null);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $rollbackWiki = $this->app->make(RollbackWikiInterface::class);
        $rollbackWiki->process($input);
    }

    /**
     * 異常系: Snapshotが見つからない場合.
     */
    public function testWhenSnapshotNotFound(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);
        $currentVersion = new Version(5);

        $currentWiki = $this->createWiki($wikiIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackWikiInput(
            $principalIdentifier,
            $wikiIdentifier,
            $targetVersion,
            ResourceType::GROUP,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $wikiIdentifier))
            ->once()
            ->andReturn($currentWiki);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $translationSetIdentifier))
            ->once()
            ->andReturn([$currentWiki]);

        $snapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $snapshotRepository->shouldReceive('findByTranslationSetIdentifierAndVersion')
            ->with(
                Mockery::on(static fn ($arg) => (string) $arg === (string) $translationSetIdentifier),
                Mockery::on(static fn ($arg) => $arg->value() === $targetVersion->value())
            )
            ->once()
            ->andReturn([]);

        $snapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $principalIdentifier))
            ->once()
            ->andReturn($principal);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        $this->expectException(SnapshotNotFoundException::class);
        $rollbackWiki = $this->app->make(RollbackWikiInterface::class);
        $rollbackWiki->process($input);
    }

    /**
     * 異常系: 翻訳セット内でバージョン不一致の場合.
     */
    public function testWhenVersionMismatch(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifierKo = new WikiIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifierJa = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(2);

        $currentWikiKo = $this->createWiki($wikiIdentifierKo, $translationSetIdentifier, new Version(5), Language::KOREAN);
        $currentWikiJa = $this->createWiki($wikiIdentifierJa, $translationSetIdentifier, new Version(4), Language::JAPANESE);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackWikiInput(
            $principalIdentifier,
            $wikiIdentifierKo,
            $targetVersion,
            ResourceType::GROUP,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $wikiIdentifierKo))
            ->once()
            ->andReturn($currentWikiKo);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $translationSetIdentifier))
            ->once()
            ->andReturn([$currentWikiKo, $currentWikiJa]);

        $snapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $principalIdentifier))
            ->once()
            ->andReturn($principal);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        $this->expectException(VersionMismatchException::class);
        $rollbackWiki = $this->app->make(RollbackWikiInterface::class);
        $rollbackWiki->process($input);
    }

    /**
     * 異常系: targetVersionが現在のバージョン以上の場合.
     */
    public function testWhenInvalidRollbackTargetVersion(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(5);
        $currentVersion = new Version(5);

        $currentWiki = $this->createWiki($wikiIdentifier, $translationSetIdentifier, $currentVersion);

        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            []
        );

        $input = new RollbackWikiInput(
            $principalIdentifier,
            $wikiIdentifier,
            $targetVersion,
            ResourceType::GROUP,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $wikiIdentifier))
            ->once()
            ->andReturn($currentWiki);

        $snapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $snapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(static fn ($arg) => (string) $arg === (string) $principalIdentifier))
            ->once()
            ->andReturn($principal);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $snapshotRepository);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $snapshotFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        $this->expectException(InvalidRollbackTargetVersionException::class);
        $rollbackWiki = $this->app->make(RollbackWikiInterface::class);
        $rollbackWiki->process($input);
    }

    private function createWiki(
        WikiIdentifier $identifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN,
    ): Wiki {
        $basic = new GroupBasic(
            name: new Name('Test Wiki'),
            normalizedName: 'test wiki',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('Fan'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );

        return new Wiki(
            $identifier,
            $translationSetIdentifier,
            new Slug('test-slug'),
            $language,
            ResourceType::GROUP,
            $basic,
            new SectionContentCollection(),
            new Color('#FF5733'),
            $version,
        );
    }

    private function createSnapshot(
        WikiIdentifier $identifier,
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
        Language $language = Language::KOREAN,
    ): WikiSnapshot {
        $basic = new GroupBasic(
            name: new Name('Snapshot Wiki Name'),
            normalizedName: 'snapshot wiki name',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('SnapshotFan'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );

        return new WikiSnapshot(
            new WikiSnapshotIdentifier(StrTestHelper::generateUuid()),
            $identifier,
            $translationSetIdentifier,
            new Slug('test-slug'),
            $language,
            ResourceType::GROUP,
            $basic,
            new SectionContentCollection(),
            new Color('#0000FF'),
            $version,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            new DateTimeImmutable(),
        );
    }

    private function createHistory(
        PrincipalIdentifier $actorIdentifier,
        WikiIdentifier $wikiIdentifier,
    ): WikiHistory {
        return new WikiHistory(
            new WikiHistoryIdentifier(StrTestHelper::generateUuid()),
            HistoryActionType::Rollback,
            $actorIdentifier,
            null,
            $wikiIdentifier,
            null,
            null,
            null,
            new Version(5),
            new Version(2),
            new Name('Test Wiki'),
            new DateTimeImmutable(),
        );
    }
}

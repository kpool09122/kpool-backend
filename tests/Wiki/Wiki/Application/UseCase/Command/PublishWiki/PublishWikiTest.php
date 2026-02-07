<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\PublishWiki;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Application\Service\ContributionPointServiceInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\Exception\InconsistentVersionException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiInterface;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\Factory\WikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Factory\WikiSnapshotFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\WikiServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiSnapshotIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishWikiTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $wikiFactory = Mockery::mock(WikiFactoryInterface::class);
        $this->app->instance(WikiFactoryInterface::class, $wikiFactory);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $wikiSnapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $wikiSnapshotFactory);
        $wikiSnapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $wikiSnapshotRepository);
        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $this->assertInstanceOf(PublishWiki::class, $publishWiki);
    }

    /**
     * 正常系：正しく変更されたWikiが公開されること（すでに一度公開されたことがある場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWhenAlreadyPublished(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $dummyPublishWiki = $this->createDummyPublishWiki(
            hasPublishedWiki: true,
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
        );

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            $dummyPublishWiki->publishedWikiIdentifier,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->publishedWikiIdentifier)
            ->andReturn($dummyPublishWiki->publishedWiki);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->publishedWiki)
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldReceive('delete')
            ->once()
            ->with($dummyPublishWiki->draftWiki)
            ->andReturn(null);

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiService->shouldReceive('hasConsistentVersions')
            ->once()
            ->with($dummyPublishWiki->translationSetIdentifier)
            ->andReturn(true);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishWiki->history);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->history)
            ->andReturn(null);

        // スナップショット関連のモック（既存の公開済みWikiがある場合はスナップショットを保存）
        $wikiSnapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $wikiSnapshotFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishWiki->publishedWiki)
            ->andReturn($dummyPublishWiki->snapshot);

        $wikiSnapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $wikiSnapshotRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->snapshot)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $wikiSnapshotFactory);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $wikiSnapshotRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $publishedWiki = $publishWiki->process($input);
        $this->assertSame((string) $dummyPublishWiki->publishedWikiIdentifier, (string) $publishedWiki->wikiIdentifier());
        $this->assertSame($dummyPublishWiki->language->value, $publishedWiki->language()->value);
        $this->assertSame((string) $dummyPublishWiki->name, (string) $publishedWiki->basic()->name());
        $this->assertSame($dummyPublishWiki->publishedVersion->value() + 1, $publishedWiki->version()->value());
    }

    /**
     * 正常系：正しく変更されたWikiが公開されること（初めて公開する場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessForTheFirstTime(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $dummyPublishWiki = $this->createDummyPublishWiki(
            hasPublishedWiki: false,
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
        );

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            $dummyPublishWiki->publishedWikiIdentifier,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->createdWiki)
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldReceive('delete')
            ->once()
            ->with($dummyPublishWiki->draftWiki)
            ->andReturn(null);

        $wikiFactory = Mockery::mock(WikiFactoryInterface::class);
        $wikiFactory->shouldReceive('create')
            ->once()
            ->with(
                $dummyPublishWiki->translationSetIdentifier,
                $dummyPublishWiki->slug,
                $dummyPublishWiki->language,
                $dummyPublishWiki->resourceType,
                $dummyPublishWiki->basic,
            )
            ->andReturn($dummyPublishWiki->createdWiki);

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiService->shouldReceive('hasConsistentVersions')
            ->once()
            ->with($dummyPublishWiki->translationSetIdentifier)
            ->andReturn(true);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishWiki->history);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->history)
            ->andReturn(null);

        // スナップショット関連のモック（初回公開時はスナップショットを保存しない）
        $wikiSnapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $wikiSnapshotFactory->shouldNotReceive('create');

        $wikiSnapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $wikiSnapshotRepository->shouldNotReceive('save');

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiFactoryInterface::class, $wikiFactory);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $wikiSnapshotFactory);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $wikiSnapshotRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $publishedWiki = $publishWiki->process($input);
        $this->assertSame((string) $dummyPublishWiki->publishedWikiIdentifier, (string) $publishedWiki->wikiIdentifier());
        $this->assertSame($dummyPublishWiki->language->value, $publishedWiki->language()->value);
        $this->assertSame((string) $dummyPublishWiki->translationSetIdentifier, (string) $publishedWiki->translationSetIdentifier());
        $this->assertSame($dummyPublishWiki->version->value(), $publishedWiki->version()->value());
    }

    /**
     * 異常系：指定したIDに紐づく下書きWikiが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundWiki(): void
    {
        $dummyPublishWiki = $this->createDummyPublishWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            $dummyPublishWiki->publishedWikiIdentifier,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturnNull();
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(WikiNotFoundException::class);
        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $publishWiki->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyPublishWiki = $this->createDummyPublishWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            $dummyPublishWiki->publishedWikiIdentifier,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $publishWiki->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummyPublishWiki = $this->createDummyPublishWiki(status: ApprovalStatus::Approved);
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            $dummyPublishWiki->publishedWikiIdentifier,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(InvalidStatusException::class);
        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $publishWiki->process($input);
    }

    /**
     * 異常系：同じ翻訳セットの公開Wikiのバージョンが揃っていない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testInconsistentVersions(): void
    {
        $dummyPublishWiki = $this->createDummyPublishWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            $dummyPublishWiki->publishedWikiIdentifier,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiService->shouldReceive('hasConsistentVersions')
            ->once()
            ->with($dummyPublishWiki->translationSetIdentifier)
            ->andReturn(false);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(InconsistentVersionException::class);
        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $publishWiki->process($input);
    }

    /**
     * 異常系：公開されているWiki情報が取得できない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundPublishedWiki(): void
    {
        $dummyPublishWiki = $this->createDummyPublishWiki(hasPublishedWiki: true);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            $dummyPublishWiki->publishedWikiIdentifier,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->publishedWikiIdentifier)
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiService->shouldReceive('hasConsistentVersions')
            ->once()
            ->with($dummyPublishWiki->translationSetIdentifier)
            ->andReturn(true);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(WikiNotFoundException::class);
        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $publishWiki->process($input);
    }

    /**
     * 異常系：公開権限がない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testDisallowedRole(): void
    {
        $dummyPublishWiki = $this->createDummyPublishWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            null,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(DisallowedException::class);
        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $publishWiki->process($input);
    }

    /**
     * 正常系：ADMINISTRATORがWikiを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $dummyPublishWiki = $this->createDummyPublishWiki(
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
        );

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            null,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->createdWiki)
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldReceive('delete')
            ->once()
            ->with($dummyPublishWiki->draftWiki)
            ->andReturn(null);

        $wikiFactory = Mockery::mock(WikiFactoryInterface::class);
        $wikiFactory->shouldReceive('create')
            ->once()
            ->with(
                $dummyPublishWiki->translationSetIdentifier,
                $dummyPublishWiki->slug,
                $dummyPublishWiki->language,
                $dummyPublishWiki->resourceType,
                $dummyPublishWiki->basic,
            )
            ->andReturn($dummyPublishWiki->createdWiki);

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiService->shouldReceive('hasConsistentVersions')
            ->once()
            ->with($dummyPublishWiki->translationSetIdentifier)
            ->andReturn(true);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishWiki->history);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiFactoryInterface::class, $wikiFactory);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $publishWiki->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $dummyPublishWiki->status);
    }

    /**
     * 正常系：approverIdentifierがnullでない場合、grantPointsが呼ばれること（新規作成時）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessGrantsContributionPointsOnNewCreation(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $dummyPublishWiki = $this->createDummyPublishWiki(
            hasPublishedWiki: false,
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
            approverIdentifier: $approverIdentifier,
            mergerIdentifier: $mergerIdentifier,
        );

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            $dummyPublishWiki->publishedWikiIdentifier,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldReceive('delete')
            ->once()
            ->with($dummyPublishWiki->draftWiki)
            ->andReturn(null);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->createdWiki)
            ->andReturn(null);

        $wikiFactory = Mockery::mock(WikiFactoryInterface::class);
        $wikiFactory->shouldReceive('create')
            ->once()
            ->with(
                $dummyPublishWiki->translationSetIdentifier,
                $dummyPublishWiki->slug,
                $dummyPublishWiki->language,
                $dummyPublishWiki->resourceType,
                $dummyPublishWiki->basic,
            )
            ->andReturn($dummyPublishWiki->createdWiki);

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiService->shouldReceive('hasConsistentVersions')
            ->once()
            ->with($dummyPublishWiki->translationSetIdentifier)
            ->andReturn(true);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishWiki->history);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->history)
            ->andReturn(null);

        $wikiSnapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $wikiSnapshotFactory->shouldNotReceive('create');

        $wikiSnapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $wikiSnapshotRepository->shouldNotReceive('save');

        // ContributionPointServiceのモック - grantPointsが正しいパラメータで呼ばれることを検証
        $contributionPointService = Mockery::mock(ContributionPointServiceInterface::class);
        $contributionPointService->shouldReceive('grantPoints')
            ->once()
            ->with(
                $dummyPublishWiki->editorIdentifier,
                $approverIdentifier,
                $mergerIdentifier,
                $dummyPublishWiki->resourceType,
                (string) $dummyPublishWiki->createdWiki->wikiIdentifier(),
                true, // isNewCreation = true for first time publish
            )
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiFactoryInterface::class, $wikiFactory);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $wikiSnapshotFactory);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $wikiSnapshotRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(ContributionPointServiceInterface::class, $contributionPointService);

        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $result = $publishWiki->process($input);

        $this->assertSame((string) $dummyPublishWiki->publishedWikiIdentifier, (string) $result->wikiIdentifier());
    }

    /**
     * 正常系：approverIdentifierがnullでない場合、grantPointsが呼ばれること（更新時）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessGrantsContributionPointsOnUpdate(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $dummyPublishWiki = $this->createDummyPublishWiki(
            hasPublishedWiki: true,
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
            approverIdentifier: $approverIdentifier,
            mergerIdentifier: $mergerIdentifier,
        );

        $input = new PublishWikiInput(
            $dummyPublishWiki->wikiIdentifier,
            $dummyPublishWiki->publishedWikiIdentifier,
            $principalIdentifier,
            $dummyPublishWiki->resourceType,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->wikiIdentifier)
            ->andReturn($dummyPublishWiki->draftWiki);
        $draftWikiRepository->shouldReceive('delete')
            ->once()
            ->with($dummyPublishWiki->draftWiki)
            ->andReturn(null);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWiki->publishedWikiIdentifier)
            ->andReturn($dummyPublishWiki->publishedWiki);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->publishedWiki)
            ->andReturn(null);

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiService->shouldReceive('hasConsistentVersions')
            ->once()
            ->with($dummyPublishWiki->translationSetIdentifier)
            ->andReturn(true);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishWiki->history);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->history)
            ->andReturn(null);

        // スナップショット関連のモック（既存の公開済みWikiがある場合はスナップショットを保存）
        $wikiSnapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $wikiSnapshotFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishWiki->publishedWiki)
            ->andReturn($dummyPublishWiki->snapshot);

        $wikiSnapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $wikiSnapshotRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWiki->snapshot)
            ->andReturn(null);

        // ContributionPointServiceのモック - grantPointsが正しいパラメータで呼ばれることを検証
        $contributionPointService = Mockery::mock(ContributionPointServiceInterface::class);
        $contributionPointService->shouldReceive('grantPoints')
            ->once()
            ->with(
                $dummyPublishWiki->editorIdentifier,
                $approverIdentifier,
                $mergerIdentifier,
                $dummyPublishWiki->resourceType,
                (string) $dummyPublishWiki->publishedWiki->wikiIdentifier(),
                false, // isNewCreation = false for update
            )
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiSnapshotFactoryInterface::class, $wikiSnapshotFactory);
        $this->app->instance(WikiSnapshotRepositoryInterface::class, $wikiSnapshotRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(ContributionPointServiceInterface::class, $contributionPointService);

        $publishWiki = $this->app->make(PublishWikiInterface::class);
        $result = $publishWiki->process($input);

        $this->assertSame((string) $dummyPublishWiki->publishedWikiIdentifier, (string) $result->wikiIdentifier());
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param ApprovalStatus $status
     * @param bool $hasPublishedWiki
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @param PrincipalIdentifier|null $approverIdentifier
     * @param PrincipalIdentifier|null $mergerIdentifier
     * @return PublishWikiTestData
     */
    private function createDummyPublishWiki(
        ApprovalStatus $status = ApprovalStatus::UnderReview,
        bool $hasPublishedWiki = false,
        ?PrincipalIdentifier $operatorIdentifier = null,
        ?PrincipalIdentifier $approverIdentifier = null,
        ?PrincipalIdentifier $mergerIdentifier = null,
    ): PublishWikiTestData {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $publishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $resourceType = ResourceType::GROUP;
        $slug = new Slug('twice');
        $name = new Name('TWICE');

        $basic = new GroupBasic(
            name: $name,
            normalizedName: 'twice',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('ONCE'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );
        $sections = new SectionContentCollection();
        $themeColor = new Color('#FF5733');

        $draftWiki = new DraftWiki(
            $wikiIdentifier,
            $hasPublishedWiki ? $publishedWikiIdentifier : null,
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $basic,
            $sections,
            $themeColor,
            $status,
            $editorIdentifier,
            $approverIdentifier,
            $mergerIdentifier,
        );

        // 公開済みのWikiエンティティ（既存データを想定）
        $exBasic = new GroupBasic(
            name: new Name('aespa'),
            normalizedName: 'aespa',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('MY'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );
        $exSections = new SectionContentCollection();
        $publishedVersion = new Version(1);
        $publishedWiki = new Wiki(
            $publishedWikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $exBasic,
            $exSections,
            new Color('#0000FF'),
            $publishedVersion,
        );

        // 新規作成用のWiki
        $version = new Version(1);
        $createdWiki = new Wiki(
            $publishedWikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $basic,
            new SectionContentCollection(),
            null,
            $version,
        );

        $historyIdentifier = new WikiHistoryIdentifier(StrTestHelper::generateUuid());
        $history = new WikiHistory(
            $historyIdentifier,
            HistoryActionType::Publish,
            $operatorIdentifier ?? new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $draftWiki->editorIdentifier(),
            $hasPublishedWiki ? $publishedWikiIdentifier : null,
            new DraftWikiIdentifier((string) $draftWiki->wikiIdentifier()),
            $draftWiki->status(),
            null,
            null,
            null,
            $draftWiki->basic()->name(),
            new DateTimeImmutable(),
        );

        // 公開済みWikiのスナップショット（更新時用）
        $snapshot = new WikiSnapshot(
            new WikiSnapshotIdentifier(StrTestHelper::generateUuid()),
            $publishedWiki->wikiIdentifier(),
            $publishedWiki->translationSetIdentifier(),
            $publishedWiki->slug(),
            $publishedWiki->language(),
            $publishedWiki->resourceType(),
            $publishedWiki->basic(),
            $publishedWiki->sections(),
            $publishedWiki->themeColor(),
            $publishedWiki->version(),
            $publishedWiki->editorIdentifier(),
            $publishedWiki->approverIdentifier(),
            $publishedWiki->mergerIdentifier(),
            $publishedWiki->sourceEditorIdentifier(),
            $publishedWiki->mergedAt(),
            $publishedWiki->translatedAt(),
            $publishedWiki->approvedAt(),
            new DateTimeImmutable('2024-01-01 00:00:00'),
        );

        return new PublishWikiTestData(
            $wikiIdentifier,
            $publishedWikiIdentifier,
            $editorIdentifier,
            $language,
            $resourceType,
            $name,
            $basic,
            $sections,
            $themeColor,
            $status,
            $translationSetIdentifier,
            $slug,
            $draftWiki,
            $publishedWiki,
            $createdWiki,
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
readonly class PublishWikiTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public DraftWikiIdentifier      $wikiIdentifier,
        public WikiIdentifier           $publishedWikiIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public ResourceType             $resourceType,
        public Name                     $name,
        public GroupBasic               $basic,
        public SectionContentCollection $sections,
        public ?Color                   $themeColor,
        public ApprovalStatus           $status,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Slug                     $slug,
        public DraftWiki                $draftWiki,
        public Wiki                     $publishedWiki,
        public Wiki                     $createdWiki,
        public Version                  $version,
        public Version                  $publishedVersion,
        public WikiHistoryIdentifier    $historyIdentifier,
        public WikiHistory              $history,
        public WikiSnapshot             $snapshot,
    ) {
    }
}

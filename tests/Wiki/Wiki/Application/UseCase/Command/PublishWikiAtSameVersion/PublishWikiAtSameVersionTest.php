<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
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
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;
use Source\Wiki\Wiki\Application\Exception\PublishedWikiRequiredException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\Service\VideoLinkCollectionRegistrationServiceInterface;
use Source\Wiki\Wiki\Application\Service\WikiWorkflowNotificationServiceInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion\PublishWikiAtSameVersion;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion\PublishWikiAtSameVersionInput;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion\PublishWikiAtSameVersionInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion\PublishWikiAtSameVersionOutput;
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
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\HexColor;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiSnapshotIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\Helper\VisitorLocationTestHelper;
use Tests\TestCase;

class PublishWikiAtSameVersionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $videoLinkCollectionRegistrationService = Mockery::mock(VideoLinkCollectionRegistrationServiceInterface::class);
        $videoLinkCollectionRegistrationService->shouldReceive('register')->byDefault();
        $this->app()->instance(VideoLinkCollectionRegistrationServiceInterface::class, $videoLinkCollectionRegistrationService);
        $this->app()->instance(WikiWorkflowNotificationServiceInterface::class, Mockery::mock(WikiWorkflowNotificationServiceInterface::class));
    }

    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $wikiFactory = Mockery::mock(WikiFactoryInterface::class);
        $this->app()->instance(WikiFactoryInterface::class, $wikiFactory);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $wikiSnapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $this->app()->instance(WikiSnapshotFactoryInterface::class, $wikiSnapshotFactory);
        $wikiSnapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $this->app()->instance(WikiSnapshotRepositoryInterface::class, $wikiSnapshotRepository);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $this->assertInstanceOf(PublishWikiAtSameVersion::class, $publishWiki);
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
    #[DataProviderExternal(VisitorLocationTestHelper::class, 'locations')]
    public function testProcessWhenAlreadyPublished(VisitorLocation $visitorLocation): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $dummyPublishWikiAtSameVersion = $this->createDummyPublishWikiAtSameVersion(
            hasPublishedWiki: true,
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
        );
        $videoLinkCollectionRegistrationService = Mockery::mock(VideoLinkCollectionRegistrationServiceInterface::class);
        $videoLinkCollectionRegistrationService->shouldReceive('register')->once()->with(ResourceType::GROUP, $dummyPublishWikiAtSameVersion->publishedWikiIdentifier);
        $this->app()->instance(VideoLinkCollectionRegistrationServiceInterface::class, $videoLinkCollectionRegistrationService);

        $input = new PublishWikiAtSameVersionInput(
            $dummyPublishWikiAtSameVersion->wikiIdentifier,
            $principalIdentifier,
            $dummyPublishWikiAtSameVersion->resourceType,
            visitorLocation: $visitorLocation,
        );
        $title = new SeoTitle('TWICE Wiki');
        $metaDescription = new MetaDescription('Published profile for TWICE.');
        $keywords = new SeoKeywords(['TWICE', 'publish']);
        $dummyPublishWikiAtSameVersion->draftWiki->setTitle($title);
        $dummyPublishWikiAtSameVersion->draftWiki->setMetaDescription($metaDescription);
        $dummyPublishWikiAtSameVersion->draftWiki->setKeywords($keywords);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->publishedWiki);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWiki)
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->wikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->draftWiki);
        $draftWikiRepository->shouldReceive('delete')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->draftWiki)
            ->andReturn(null);

        $wikiService = Mockery::mock(WikiServiceInterface::class);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->withArgs(static fn (...$args) => $args[1] === $input->principalIdentifier()
                && $args[10] === $input->visitorLocation())
            ->once()
            ->andReturn($dummyPublishWikiAtSameVersion->history);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->history)
            ->andReturn(null);

        // スナップショット関連のモック（既存の公開済みWikiがある場合はスナップショットを保存）
        $wikiSnapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $wikiSnapshotFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWiki)
            ->andReturn($dummyPublishWikiAtSameVersion->snapshot);

        $wikiSnapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $wikiSnapshotRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->snapshot)
            ->andReturn(null);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(WikiSnapshotFactoryInterface::class, $wikiSnapshotFactory);
        $this->app()->instance(WikiSnapshotRepositoryInterface::class, $wikiSnapshotRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiWorkflowNotificationService = Mockery::mock(WikiWorkflowNotificationServiceInterface::class);
        $wikiWorkflowNotificationService->shouldReceive('notifyPublished')->once()->with($input->wikiIdentifier(), (string) $dummyPublishWikiAtSameVersion->draftWiki->basic()->name(), $dummyPublishWikiAtSameVersion->slug, $dummyPublishWikiAtSameVersion->language);
        $this->app()->instance(WikiWorkflowNotificationServiceInterface::class, $wikiWorkflowNotificationService);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $output = new PublishWikiAtSameVersionOutput();
        $publishWiki->process($input, $output);
        $result = $output->toArray();
        $this->assertSame($dummyPublishWikiAtSameVersion->language->value, $result['language']);
        $this->assertSame((string) $dummyPublishWikiAtSameVersion->name, $result['name']);
        $this->assertSame($dummyPublishWikiAtSameVersion->publishedVersion->value(), $result['version']);
        $this->assertSame($title, $dummyPublishWikiAtSameVersion->publishedWiki->title());
        $this->assertSame($metaDescription, $dummyPublishWikiAtSameVersion->publishedWiki->metaDescription());
        $this->assertSame($keywords, $dummyPublishWikiAtSameVersion->publishedWiki->keywords());
        $this->assertNotNull($dummyPublishWikiAtSameVersion->publishedWiki->publishedAt());
    }

    /**
     * 正常系：下書きのpublishedWikiIdentifierで公開Wikiを更新できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessUsesDraftPublishedWikiIdentifier(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));

        $dummyPublishWikiAtSameVersion = $this->createDummyPublishWikiAtSameVersion(
            hasPublishedWiki: true,
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
        );

        $input = new PublishWikiAtSameVersionInput(
            $dummyPublishWikiAtSameVersion->wikiIdentifier,
            $principalIdentifier,
            $dummyPublishWikiAtSameVersion->resourceType,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->publishedWiki);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWiki)
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->wikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->draftWiki);
        $draftWikiRepository->shouldReceive('delete')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->draftWiki)
            ->andReturn(null);

        $wikiService = Mockery::mock(WikiServiceInterface::class);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->withArgs(static fn (...$args) => $args[1] === $input->principalIdentifier()
                && $args[10] === $input->visitorLocation())
            ->once()
            ->with(
                HistoryActionType::Publish,
                $principalIdentifier,
                $dummyPublishWikiAtSameVersion->editorIdentifier,
                $dummyPublishWikiAtSameVersion->publishedWikiIdentifier,
                Mockery::type(DraftWikiIdentifier::class),
                ApprovalStatus::Approved,
                null,
                null,
                null,
                $dummyPublishWikiAtSameVersion->name,
                $input->visitorLocation(),
            )
            ->andReturn($dummyPublishWikiAtSameVersion->history);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->history)
            ->andReturn(null);

        $wikiSnapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $wikiSnapshotFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWiki)
            ->andReturn($dummyPublishWikiAtSameVersion->snapshot);

        $wikiSnapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $wikiSnapshotRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->snapshot)
            ->andReturn(null);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(WikiSnapshotFactoryInterface::class, $wikiSnapshotFactory);
        $this->app()->instance(WikiSnapshotRepositoryInterface::class, $wikiSnapshotRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $wikiWorkflowNotificationService = Mockery::mock(WikiWorkflowNotificationServiceInterface::class);
        $wikiWorkflowNotificationService->shouldReceive('notifyPublished')->once()->with($input->wikiIdentifier(), (string) $dummyPublishWikiAtSameVersion->draftWiki->basic()->name(), $dummyPublishWikiAtSameVersion->slug, $dummyPublishWikiAtSameVersion->language);
        $this->app()->instance(WikiWorkflowNotificationServiceInterface::class, $wikiWorkflowNotificationService);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $output = new PublishWikiAtSameVersionOutput();
        $publishWiki->process($input, $output);

        $result = $output->toArray();
        $this->assertSame($dummyPublishWikiAtSameVersion->publishedVersion->value(), $result['version']);
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
        $dummyPublishWikiAtSameVersion = $this->createDummyPublishWikiAtSameVersion();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishWikiAtSameVersionInput(
            $dummyPublishWikiAtSameVersion->wikiIdentifier,
            $principalIdentifier,
            $dummyPublishWikiAtSameVersion->resourceType,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->wikiIdentifier)
            ->andReturnNull();
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(WikiNotFoundException::class);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $publishWiki->process($input, new PublishWikiAtSameVersionOutput());
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
        $dummyPublishWikiAtSameVersion = $this->createDummyPublishWikiAtSameVersion();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishWikiAtSameVersionInput(
            $dummyPublishWikiAtSameVersion->wikiIdentifier,
            $principalIdentifier,
            $dummyPublishWikiAtSameVersion->resourceType,
            visitorLocation: new VisitorLocation('JP', '01'),
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
            ->with($dummyPublishWikiAtSameVersion->wikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $publishWiki->process($input, new PublishWikiAtSameVersionOutput());
    }

    /**
     * 異常系：承認ステータスがApproved以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummyPublishWikiAtSameVersion = $this->createDummyPublishWikiAtSameVersion(status: ApprovalStatus::UnderReview);
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishWikiAtSameVersionInput(
            $dummyPublishWikiAtSameVersion->wikiIdentifier,
            $principalIdentifier,
            $dummyPublishWikiAtSameVersion->resourceType,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->wikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(InvalidStatusException::class);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $publishWiki->process($input, new PublishWikiAtSameVersionOutput());
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
        $dummyPublishWikiAtSameVersion = $this->createDummyPublishWikiAtSameVersion(hasPublishedWiki: true);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));

        $input = new PublishWikiAtSameVersionInput(
            $dummyPublishWikiAtSameVersion->wikiIdentifier,
            $principalIdentifier,
            $dummyPublishWikiAtSameVersion->resourceType,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWikiIdentifier)
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->wikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(WikiNotFoundException::class);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $publishWiki->process($input, new PublishWikiAtSameVersionOutput());
    }

    public function testRejectsFirstPublicationWithoutLinkedWiki(): void
    {
        $dummyPublishWikiAtSameVersion = $this->createDummyPublishWikiAtSameVersion(hasPublishedWiki: false);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));

        $input = new PublishWikiAtSameVersionInput(
            $dummyPublishWikiAtSameVersion->wikiIdentifier,
            $principalIdentifier,
            $dummyPublishWikiAtSameVersion->resourceType,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldNotReceive('findById');
        $wikiRepository->shouldNotReceive('save');

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->wikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(PublishedWikiRequiredException::class);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $publishWiki->process($input, new PublishWikiAtSameVersionOutput());
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
        $dummyPublishWikiAtSameVersion = $this->createDummyPublishWikiAtSameVersion();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));

        $input = new PublishWikiAtSameVersionInput(
            $dummyPublishWikiAtSameVersion->wikiIdentifier,
            $principalIdentifier,
            $dummyPublishWikiAtSameVersion->resourceType,
            visitorLocation: new VisitorLocation('JP', '01'),
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
            ->with($dummyPublishWikiAtSameVersion->wikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->draftWiki);
        $draftWikiRepository->shouldNotReceive('delete');

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(DisallowedException::class);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $publishWiki->process($input, new PublishWikiAtSameVersionOutput());
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
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $dummyPublishWikiAtSameVersion = $this->createDummyPublishWikiAtSameVersion(
            hasPublishedWiki: true,
            operatorIdentifier: new PrincipalIdentifier((string) $principalIdentifier),
            approverIdentifier: $approverIdentifier,
            mergerIdentifier: $mergerIdentifier,
        );

        $input = new PublishWikiAtSameVersionInput(
            $dummyPublishWikiAtSameVersion->wikiIdentifier,
            $principalIdentifier,
            $dummyPublishWikiAtSameVersion->resourceType,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->wikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->draftWiki);
        $draftWikiRepository->shouldReceive('delete')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->draftWiki)
            ->andReturn(null);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWikiIdentifier)
            ->andReturn($dummyPublishWikiAtSameVersion->publishedWiki);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWiki)
            ->andReturn(null);

        $wikiService = Mockery::mock(WikiServiceInterface::class);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->withArgs(static fn (...$args) => $args[1] === $input->principalIdentifier()
                && $args[10] === $input->visitorLocation())
            ->once()
            ->andReturn($dummyPublishWikiAtSameVersion->history);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->history)
            ->andReturn(null);

        // スナップショット関連のモック（既存の公開済みWikiがある場合はスナップショットを保存）
        $wikiSnapshotFactory = Mockery::mock(WikiSnapshotFactoryInterface::class);
        $wikiSnapshotFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->publishedWiki)
            ->andReturn($dummyPublishWikiAtSameVersion->snapshot);

        $wikiSnapshotRepository = Mockery::mock(WikiSnapshotRepositoryInterface::class);
        $wikiSnapshotRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishWikiAtSameVersion->snapshot)
            ->andReturn(null);

        // ContributionPointServiceのモック - grantPointsが正しいパラメータで呼ばれることを検証
        $contributionPointService = Mockery::mock(ContributionPointServiceInterface::class);
        $contributionPointService->shouldReceive('grantPoints')
            ->once()
            ->with(
                $dummyPublishWikiAtSameVersion->editorIdentifier,
                $approverIdentifier,
                $mergerIdentifier,
                $dummyPublishWikiAtSameVersion->resourceType,
                (string) $dummyPublishWikiAtSameVersion->publishedWiki->wikiIdentifier(),
                false, // isNewCreation = false for update
                false, // isTranslation
            )
            ->andReturn(null);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(WikiServiceInterface::class, $wikiService);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(WikiSnapshotFactoryInterface::class, $wikiSnapshotFactory);
        $this->app()->instance(WikiSnapshotRepositoryInterface::class, $wikiSnapshotRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app()->instance(ContributionPointServiceInterface::class, $contributionPointService);

        $wikiWorkflowNotificationService = Mockery::mock(WikiWorkflowNotificationServiceInterface::class);
        $wikiWorkflowNotificationService->shouldReceive('notifyPublished')->once()->with($input->wikiIdentifier(), (string) $dummyPublishWikiAtSameVersion->draftWiki->basic()->name(), $dummyPublishWikiAtSameVersion->slug, $dummyPublishWikiAtSameVersion->language);
        $this->app()->instance(WikiWorkflowNotificationServiceInterface::class, $wikiWorkflowNotificationService);
        $publishWiki = $this->app()->make(PublishWikiAtSameVersionInterface::class);
        $output = new PublishWikiAtSameVersionOutput();
        $publishWiki->process($input, $output);
        $result = $output->toArray();

        $this->assertNotNull($result['language']);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param ApprovalStatus $status
     * @param bool $hasPublishedWiki
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @param PrincipalIdentifier|null $approverIdentifier
     * @param PrincipalIdentifier|null $mergerIdentifier
     * @return PublishWikiAtSameVersionTestData
     */
    private function createDummyPublishWikiAtSameVersion(
        ApprovalStatus $status = ApprovalStatus::Approved,
        bool $hasPublishedWiki = false,
        ?PrincipalIdentifier $operatorIdentifier = null,
        ?PrincipalIdentifier $approverIdentifier = null,
        ?PrincipalIdentifier $mergerIdentifier = null,
        ?DateTimeImmutable $translatedAt = null,
        ?Version $createdVersion = null,
        ?Version $publishedVersion = null,
    ): PublishWikiAtSameVersionTestData {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $publishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $resourceType = ResourceType::GROUP;
        $slug = new Slug('gr-twice');
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
        );
        $sections = new SectionContentCollection();
        $themeColor = new HexColor('#ff5733');

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
            translatedAt: $translatedAt,
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
        );
        $exSections = new SectionContentCollection();
        $publishedVersion ??= new Version(1);
        $publishedWiki = new Wiki(
            $publishedWikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $exBasic,
            $exSections,
            new HexColor('#0000ff'),
            $publishedVersion,
        );

        // 新規作成用のWiki
        $version = $createdVersion ?? new Version(1);
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
            new VisitorLocation(),
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

        return new PublishWikiAtSameVersionTestData(
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
readonly class PublishWikiAtSameVersionTestData
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
        public ?HexColor                   $themeColor,
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
